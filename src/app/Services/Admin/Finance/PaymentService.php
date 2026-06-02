<?php

namespace App\Services\Admin\Finance;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Admin\Finance\SortAndFilterPaymentCriteria;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Services\AbstractService;
use Illuminate\Http\Request;

class PaymentService extends AbstractService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected WalletTransactionRepository $walletTransactionRepository
    ) {}

    public function list(Request $request)
    {
        $limit = $request->query('limit', 10);
        $filters = $request->except(['limit', 'page', 'sorts', 'keyword']);
        $sorts = $request->query('sorts', []);
        $keyword = $request->query('keyword', []);

        // Use array for search if keyword is simple string
        if (is_string($keyword)) {
            $keyword = ['code' => $keyword, 'job_code' => $keyword, 'customer_name' => $keyword, 'worker_name' => $keyword];
        }

        return $this->paymentRepository->pushCriteria(
            new SortAndFilterPaymentCriteria($filters, $sorts, $keyword ?: [])
        )->paginate($limit);
    }

    public function show($id)
    {
        $payment = $this->paymentRepository->with(['job', 'job.customer', 'job.worker', 'job.quotation', 'job.complaints'])->find($id);

        if (! $payment) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Payment not found', 404);
        }

        return $payment;
    }

    public function refund($id, $data)
    {
        $payment = $this->paymentRepository->find($id);
        if (! $payment) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Payment not found', 404);
        }

        if ($payment->status !== PaymentStatusConst::PAID) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Only paid payments can be refunded', 400);
        }

        if ($payment->job->status === JobStatusConst::REFUNDED) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job already refunded', 400);
        }

        $this->beginTransaction();
        try {
            // Update Payment
            $this->paymentRepository->update($id, [
                'status' => PaymentStatusConst::REFUNDED,
                'refunded_at' => now(),
                'refunded_amount' => $payment->amount,
                'description' => $payment->description.' | Refund Reason: '.($data['reason'] ?? 'Admin Request'),
            ]);

            // Update Job
            $payment->job->update([
                'status' => JobStatusConst::REFUNDED,
            ]);

            // Handle Worker Wallet Transaction
            // Find earning transaction for this job
            $walletTxn = $this->walletTransactionRepository->findWhere([
                'job_id' => $payment->job_id,
                'type' => \App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst::EARNING,
            ])->first();

            if ($walletTxn) {
                if ($walletTxn->status === WalletTransactionStatusConst::PENDING) {
                    // Cancel/Delete pending transaction
                    $walletTxn->delete();
                } elseif ($walletTxn->status === WalletTransactionStatusConst::RELEASED) {
                    // Critical: Worker already got money.
                    // For now, throw exception as per safe assumption
                    throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Cannot refund: Worker already received funds', 400);
                }
            }

            $this->commitTransaction();

            return $payment->refresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}

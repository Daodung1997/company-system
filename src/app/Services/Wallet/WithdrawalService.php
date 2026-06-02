<?php

namespace App\Services\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Exceptions\BusinessException;
use App\Jobs\ProcessWithdrawalJob;
use App\Repositories\Wallet\BankAccountRepository;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Repositories\Wallet\WithdrawalLogRepository;
use App\Repositories\Wallet\WithdrawalRepository;
use App\Services\AbstractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class WithdrawalService extends AbstractService
{
    protected $withdrawalRepository;

    protected $withdrawalLogRepository;

    protected $walletTransactionRepository;

    protected $bankAccountRepository;

    public function __construct(
        WithdrawalRepository $withdrawalRepository,
        WithdrawalLogRepository $withdrawalLogRepository,
        WalletTransactionRepository $walletTransactionRepository,
        BankAccountRepository $bankAccountRepository
    ) {
        $this->withdrawalRepository = $withdrawalRepository;
        $this->withdrawalLogRepository = $withdrawalLogRepository;
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->bankAccountRepository = $bankAccountRepository;
    }

    /**
     * Create withdrawal request
     */
    public function create(array $data, int $workerId)
    {
        // Validate available balance
        $availableBalance = $this->walletTransactionRepository->calculateAvailableBalance($workerId);

        if ($availableBalance <= 0) {
            throw new BusinessException(
                ExceptionCode::INSUFFICIENT_BALANCE,
                'Số dư không đủ',
                400
            );
        }

        if ($data['amount'] > $availableBalance) {
            throw new BusinessException(
                ExceptionCode::INSUFFICIENT_BALANCE,
                'Số dư không đủ',
                400
            );
        }

        // Check pending withdrawal
        if ($this->withdrawalRepository->hasPendingWithdrawal($workerId)) {
            throw new BusinessException(
                ExceptionCode::PENDING_WITHDRAWAL_EXISTS,
                'Đang có yêu cầu rút tiền chờ xử lý',
                409
            );
        }

        // Validate bank account exists and belongs to worker
        $bankAccount = $this->bankAccountRepository->find($data['bank_account_id']);

        if (! $bankAccount || $bankAccount->user_id != $workerId) {
            throw new BusinessException(
                ExceptionCode::NO_BANK_ACCOUNT,
                'Vui lòng thêm tài khoản ngân hàng',
                400
            );
        }

        $this->beginTransaction();
        try {
            // Create withdrawal request (status = REQUESTED)
            $withdrawal = $this->withdrawalRepository->create([
                'worker_id' => $workerId,
                'bank_account_id' => $data['bank_account_id'],
                'amount' => $data['amount'],
                'status' => WithdrawalStatusConst::REQUESTED,
            ]);

            $this->walletTransactionRepository->createPendingWithdrawal(
                $workerId,
                $withdrawal->id,
                $data['amount'],
                "Withdrawal request #{$withdrawal->code}",
                'SYSTEM'
            );

            $this->withdrawalLogRepository->create([
                'withdrawal_id' => $withdrawal->id,
                'event' => 'withdrawal_requested',
                'status' => WithdrawalStatusConst::REQUESTED,
                'payload' => [
                    'amount' => (float) $data['amount'],
                    'bank_account_id' => $data['bank_account_id'],
                ],
                'created_by' => 'SYSTEM',
                'updated_by' => 'SYSTEM',
            ]);

            // Log withdrawal request
            Log::info('Withdrawal request created', [
                'withdrawal_id' => $withdrawal->id,
                'worker_id' => $workerId,
                'amount' => $data['amount'],
                'bank_account_id' => $data['bank_account_id'],
            ]);

            $this->commitTransaction();

            ProcessWithdrawalJob::dispatch($withdrawal->id);

            return $withdrawal->load('bankAccount');
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * List withdrawals with pagination
     */
    public function list(Request $request, int $workerId)
    {
        $filters = $request->query('filters', []);
        $sorts = $request->query('sorts', []);
        $search = $request->query('search', []);
        $limit = $request->query('limit', App::make('config')->get('app.per_page', 20));

        // Add worker_id filter
        $filters['worker_id'] = $workerId;

        return $this->withdrawalRepository->pushCriteria(
            new \App\Repositories\Criteria\Wallet\SortAndFilterWithdrawalCriteria($filters, $sorts, $search)
        )->paginate($limit);
    }

    /**
     * Get single withdrawal with IDOR check
     */
    public function get(int $id, int $workerId)
    {
        $withdrawal = $this->withdrawalRepository->find($id);

        if (! $withdrawal) {
            throw new BusinessException(
                ExceptionCode::NOT_FOUND,
                'Không tìm thấy yêu cầu rút tiền',
                404
            );
        }

        // IDOR check
        if ($withdrawal->worker_id != $workerId) {
            throw new BusinessException(
                ExceptionCode::NOT_FOUND,
                'Không tìm thấy yêu cầu rút tiền',
                404
            );
        }

        return $withdrawal->load(['bankAccount', 'logs']);
    }
}

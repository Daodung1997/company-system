<?php

namespace App\Services\User;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentMethodTypeConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\PaymentMethod\ActivePaymentMethodCriteria;
use App\Repositories\Job\JobRepositoryInterface as JobRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Services\AbstractService;
use App\Services\Common\VnpayService;
use Illuminate\Support\Facades\Log;

class PaymentService extends AbstractService
{
    protected $paymentRepository;

    protected $jobRepository;

    protected $paymentMethodRepository;

    protected $vnpayService;

    protected $walletTransactionRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        JobRepository $jobRepository,
        PaymentMethodRepository $paymentMethodRepository,
        VnpayService $vnpayService,
        WalletTransactionRepository $walletTransactionRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->jobRepository = $jobRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->vnpayService = $vnpayService;
        $this->walletTransactionRepository = $walletTransactionRepository;
    }

    /**
     * Process cash payment for a job
     *
     * @param  int|string  $jobId
     * @param  \App\Models\User  $customer
     * @return \App\Models\Payment
     *
     * @throws BusinessException
     */
    public function processCashPayment($jobId, $customer)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR Check
        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        if ($job->status !== JobStatusConst::PENDING_PAYMENT) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Job is not in pending payment status',
                400
            );
        }

        // Check if already paid or has existing payment
        $existingPayment = $this->paymentRepository->findWhere([
            'job_id' => $jobId,
        ])->first();

        if ($existingPayment && $existingPayment->status === PaymentStatusConst::PAID) {
            throw new BusinessException(
                ExceptionCode::DUPLICATE_ENTRY,
                'Payment already completed for this job',
                409
            );
        }

        $this->beginTransaction();
        try {
            $paymentData = [
                'amount' => $job->total_amount ?? ($job->quotation_price + ($job->platform_fee ?? 0)),
                'platform_fee' => $job->platform_fee ?? 0,
                'worker_earning' => $job->quotation_price,
                'payment_method' => PaymentMethodConst::CASH,
                'status' => PaymentStatusConst::PAID,
                'paid_at' => now(),
                'description' => 'Cash payment confirmed by customer',
            ];

            if ($existingPayment) {
                $this->paymentRepository->update($existingPayment->id, $paymentData);
                $payment = $this->paymentRepository->find($existingPayment->id);
            } else {
                $paymentData['job_id'] = $jobId;
                $paymentData['created_by'] = $customer->code;
                $payment = $this->paymentRepository->create($paymentData);
            }

            // Update Job Status
            $this->jobRepository->update($jobId, [
                'status' => JobStatusConst::PAID,
                'updated_by' => $customer->id,
            ]);

            $this->commitTransaction();

            return $payment;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get payment info for a job
     *
     * @param  int|string  $jobId
     * @param  \App\Models\User  $customer
     * @return array
     *
     * @throws BusinessException
     */
    public function getPaymentInfo($jobId, $customer)
    {
        $job = $this->jobRepository->with(['serviceCategory', 'worker'])->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR Check
        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        if ($job->status !== JobStatusConst::PENDING_PAYMENT) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job is not pending payment', 400);
        }

        $paymentMethods = $this->paymentMethodRepository
            ->pushCriteria(new ActivePaymentMethodCriteria)
            ->all();

        return [
            'job_id' => $job->id,
            'service' => $job->serviceCategory ? ['id' => $job->serviceCategory->id, 'name' => $job->serviceCategory->name] : null,
            'worker' => $job->worker ? ['id' => $job->worker->id, 'name' => $job->worker->full_name] : null,
            'quotation_price' => $job->quotation_price,
            'platform_fee' => $job->platform_fee ?? 0,
            'total_amount' => $job->total_amount ?? ($job->quotation_price + ($job->platform_fee ?? 0)),
            'payment_status' => PaymentStatusConst::PENDING,
            'payment_methods' => $paymentMethods,
        ];
    }

    /**
     * Create payment for a job
     *
     * @param  int|string  $jobId
     * @param  string  $method
     * @param  \App\Models\User  $customer
     * @return \App\Models\Payment
     *
     * @throws BusinessException
     */
    public function createPayment($jobId, $method, $customer)
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR Check
        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        if ($job->status !== JobStatusConst::PENDING_PAYMENT) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job is not in pending payment status', 400);
        }

        $existingPayment = $this->paymentRepository->findWhere(['job_id' => $jobId])->first();
        if ($existingPayment && $existingPayment->status == PaymentStatusConst::PAID) {
            throw new BusinessException(ExceptionCode::DUPLICATE_ENTRY, 'Payment already completed for this job', 409);
        }

        $amount = $job->total_amount ?? ($job->quotation_price + ($job->platform_fee ?? 0));

        $paymentData = [
            'job_id' => $jobId,
            'amount' => $amount,
            'platform_fee' => $job->platform_fee ?? 0,
            'worker_earning' => $job->quotation_price,
            'payment_method' => $method,
            'status' => PaymentStatusConst::PENDING,
            'created_by' => $customer->code,
        ];

        $this->beginTransaction();
        try {
            if ($existingPayment) {
                $this->paymentRepository->update($existingPayment->id, $paymentData);
                $payment = $this->paymentRepository->find($existingPayment->id);
            } else {
                $payment = $this->paymentRepository->create($paymentData);
            }
            $this->commitTransaction();

            return $payment;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Create gateway payment (VNPay)
     *
     * @param  int|string  $jobId
     * @param  string  $method
     * @param  \App\Models\User  $customer
     * @return \App\Models\Payment
     *
     * @throws BusinessException
     */
    public function createGatewayPayment($jobId, $method, $customer)
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR Check
        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        if ($job->status !== JobStatusConst::PENDING_PAYMENT) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job is not in pending payment status', 400);
        }

        // Check if already paid
        $existingPayment = $this->paymentRepository->findWhere([
            'job_id' => $jobId,
            'status' => PaymentStatusConst::PAID,
        ])->first();

        if ($existingPayment) {
            throw new BusinessException(ExceptionCode::DUPLICATE_ENTRY, 'Payment already completed for this job', 409);
        }

        // Check if payment method is enabled
        $paymentMethod = $this->paymentMethodRepository->findWhere(['type' => $method, 'status' => CommonStatusConst::ACTIVE])->first();
        if (! $paymentMethod) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Payment method not enabled', 400);
        }

        $amount = $job->total_amount ?? ($job->quotation_price + ($job->platform_fee ?? 0));

        if ($method === PaymentMethodTypeConst::CASH) {
            return $this->processCashPayment($jobId, $customer);
        }

        $this->beginTransaction();
        try {
            // Find existing pending payment for the same job to update/reuse, preventing unique constraint violation
            $payment = $this->paymentRepository->findWhere([
                'job_id' => $jobId,
                'status' => PaymentStatusConst::PENDING,
            ])->first();

            if ($payment) {
                $this->paymentRepository->update($payment->id, [
                    'amount' => $amount,
                    'platform_fee' => $job->platform_fee ?? 0,
                    'worker_earning' => $job->quotation_price,
                    'payment_method' => $method,
                    'gateway_provider' => $method,
                ]);
                $payment = $this->paymentRepository->find($payment->id);
            } else {
                $payment = $this->paymentRepository->create([
                    'job_id' => $jobId,
                    'amount' => $amount,
                    'platform_fee' => $job->platform_fee ?? 0,
                    'worker_earning' => $job->quotation_price,
                    'payment_method' => $method,
                    'status' => PaymentStatusConst::PENDING,
                    'created_by' => $customer->code,
                    'gateway_provider' => $method,
                ]);
            }

            // Update gateway order info
            $gatewayOrderId = $payment->code.'_'.time();
            $this->paymentRepository->update($payment->id, [
                'gateway_order_id' => $gatewayOrderId,
            ]);
            $payment->gateway_order_id = $gatewayOrderId;

            // Build Pay URL
            if ($method === PaymentMethodTypeConst::VNPAY) {
                $payUrl = $this->vnpayService->buildPayUrl($payment);
                // Store pay_url temporarily in the model (not in DB unless specified, but spec implies returning it)
                $payment->pay_url = $payUrl;
            } else {
                throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Gateway not supported', 400);
            }

            $this->commitTransaction();

            return $payment;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Handle VNPay IPN
     *
     * @return array [RspCode, Message]
     */
    public function handleVnpayIpn(array $requestData)
    {
        // 1. Verify Hash
        if (! $this->vnpayService->verifyIpnHash($requestData)) {
            return [
                'RspCode' => '97',
                'Message' => 'Invalid Checksum',
            ];
        }

        // 2. Find Payment
        $txnRef = $requestData['vnp_TxnRef'];
        $payment = $this->paymentRepository->findWhere(['gateway_order_id' => $txnRef])->first();

        if (! $payment) {
            return [
                'RspCode' => '01',
                'Message' => 'Order Not Found',
            ];
        }

        // 3. Verify Amount
        $vnpAmount = $requestData['vnp_Amount'] / 100;
        if (floatval($payment->amount) != floatval($vnpAmount)) {
            return [
                'RspCode' => '04',
                'Message' => 'Invalid Amount',
            ];
        }

        // 4. Check Idempotent
        if ($payment->status !== PaymentStatusConst::PENDING) {
            return [
                'RspCode' => '02',
                'Message' => 'Order Already Confirmed',
            ];
        }

        // 5. Process Payment Status
        $this->beginTransaction();
        try {
            $responseCode = $requestData['vnp_ResponseCode'];

            if ($responseCode === '00') {
                // Success
                $this->paymentRepository->update($payment->id, [
                    'status' => PaymentStatusConst::PAID,
                    'paid_at' => now(),
                    'transaction_reference' => $requestData['vnp_TransactionNo'] ?? null,
                    'gateway_request_data' => $requestData,
                ]);

                // Update Job Status
                $this->jobRepository->update($payment->job_id, [
                    'status' => JobStatusConst::PAID,
                ]);

                // Create Wallet Transaction for Worker
                $job = $payment->job;
                if ($job && $job->worker_id) {
                    $this->walletTransactionRepository->createPendingEarning(
                        $job->worker_id,
                        $job->id,
                        $payment->worker_earning,
                        'Earning from Job #'.$job->code,
                        null,
                        'SYSTEM'
                    );
                }
            } else {
                // Failed
                $this->paymentRepository->update($payment->id, [
                    'status' => PaymentStatusConst::FAILED,
                    'gateway_request_data' => $requestData,
                ]);
            }

            $this->commitTransaction();

            return [
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ];
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('VNPay IPN Error: '.$e->getMessage());

            return [
                'RspCode' => '99',
                'Message' => 'Unknown Error',
            ];
        }
    }

    /**
     * Handle VNPay Return
     *
     * @return array [status, payment]
     */
    public function handleVnpayReturn(array $requestData)
    {
        // 1. Verify Hash
        if (! $this->vnpayService->verifyIpnHash($requestData)) {
            return [
                'status' => 'error',
                'message' => 'Invalid checksum',
            ];
        }

        // 2. Find Payment
        $txnRef = $requestData['vnp_TxnRef'];
        $payment = $this->paymentRepository->findWhere(['gateway_order_id' => $txnRef])->first();

        if (! $payment) {
            return [
                'status' => 'error',
                'message' => 'Payment not found',
            ];
        }

        $responseCode = $requestData['vnp_ResponseCode'];
        if ($responseCode === '00') {
            return [
                'status' => 'success',
                'payment' => $payment,
            ];
        } else {
            return [
                'status' => 'failed',
                'payment' => $payment,
                'response_code' => $responseCode,
            ];
        }
    }

    /**
     * Confirm payment for a job
     *
     * @param  int|string  $jobId
     * @param  array  $data
     * @param  \App\Models\User  $customer
     * @return \App\Models\Payment
     *
     * @throws BusinessException
     */
    public function confirmPayment($jobId, $data, $customer)
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR Check
        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        $payment = $this->paymentRepository->findWhere(['job_id' => $jobId])->first();
        if (! $payment) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Payment not found', 404);
        }

        if ($payment->status === PaymentStatusConst::PAID) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Already paid', 400);
        }

        $this->beginTransaction();
        try {
            $this->paymentRepository->update($payment->id, [
                'status' => PaymentStatusConst::PROCESSING,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'updated_by' => $customer->code,
            ]);

            // Side effect: Job status = paid (As per spec section 4 Side Effects)
            $this->jobRepository->update($jobId, [
                'status' => JobStatusConst::PAID,
                'updated_by' => $customer->id,
            ]);

            $this->commitTransaction();

            // Fetch updated payment
            return $this->paymentRepository->find($payment->id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * List all active payment methods
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listPaymentMethods()
    {
        return $this->paymentMethodRepository
            ->pushCriteria(new ActivePaymentMethodCriteria)
            ->all();
    }
}

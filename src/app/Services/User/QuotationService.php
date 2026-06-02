<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Notification\NotificationTypeConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeCodeConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Quotation\SortAndFilterQuotationCriteria;
use App\Repositories\Job\JobRepository;
use App\Repositories\PlatformFee\PlatformFeeRepository;
use App\Repositories\Quotation\QuotationRepository;
use App\Services\AbstractService;

class QuotationService extends AbstractService
{
    protected $quotationRepository;

    protected $jobRepository;

    protected $jobInvitedWorkerRepository;

    protected $platformFeeRepository;

    protected $notificationService;

    protected $discountService;

    public function __construct(
        QuotationRepository $quotationRepository,
        JobRepository $jobRepository,
        \App\Repositories\JobInvitedWorker\JobInvitedWorkerRepository $jobInvitedWorkerRepository,
        PlatformFeeRepository $platformFeeRepository,
        NotificationService $notificationService,
        DiscountService $discountService
    ) {
        $this->quotationRepository = $quotationRepository;
        $this->jobRepository = $jobRepository;
        $this->jobInvitedWorkerRepository = $jobInvitedWorkerRepository;
        $this->platformFeeRepository = $platformFeeRepository;
        $this->notificationService = $notificationService;
        $this->discountService = $discountService;
    }

    /**
     * Worker submits quotation for a job
     */
    public function submitQuotation($jobId, $workerId, array $data, $workerCode)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // Check job status - allow when waiting or already quoted (multiple workers can quote)
        if (! in_array($job->status, [JobStatusConst::WAITING_FOR_QUOTATION, JobStatusConst::QUOTED])) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Job is not accepting quotations',
                400
            );
        }

        // Check if worker already quoted
        $existingQuotation = $this->quotationRepository->findByJobAndWorker($jobId, $workerId);
        if ($existingQuotation) {
            throw new BusinessException(
                ExceptionCode::DUPLICATE_ENTRY,
                'You have already submitted a quotation for this job',
                409
            );
        }

        // Check invitation permission
        // If job has specific invitations, worker must be in the list
        if ($this->jobInvitedWorkerRepository->hasInvitedWorkers($jobId)) {
            if (! $this->jobInvitedWorkerRepository->isWorkerInvited($jobId, $workerId)) {
                throw new BusinessException(
                    ExceptionCode::PERMISSION_DENIED,
                    'You are not invited to quote for this job',
                    403
                );
            }
        }

        $platformFeeConfig = $this->platformFeeRepository->getActiveFeeByCode(PlatformFeeCodeConst::PLATFORM_FEE);
        $platformFeeAmount = 0;

        if ($platformFeeConfig) {
            if ($platformFeeConfig->fee_type === PlatformFeeTypeConst::PERCENTAGE) {
                $platformFeeAmount = $data['price'] * ($platformFeeConfig->amount / 100);
            } else {
                $platformFeeAmount = $platformFeeConfig->amount;
            }
        }

        $totalAmount = $data['price'] + $platformFeeAmount;

        $this->beginTransaction();
        try {
            $quotation = $this->quotationRepository->create([
                'job_id' => $jobId,
                'worker_id' => $workerId,
                'price' => $data['price'],
                'platform_fee' => $platformFeeAmount,
                'total_amount' => $totalAmount,
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => QuotationStatusConst::PENDING,
                'created_by' => $workerCode,
            ]);

            // Update job status to 'quoted' if this is first quotation
            if ($job->status === JobStatusConst::WAITING_FOR_QUOTATION) {
                $this->jobRepository->update($jobId, [
                    'status' => JobStatusConst::QUOTED,
                ]);
            }

            // Notify customer about new quotation
            $this->notificationService->sendNotification(
                $job->customer_id,
                NotificationTypeConst::JOB_QUOTATION_RECEIVED,
                __('notification.job_quotation_received.title'),
                __('notification.job_quotation_received.body', ['job_code' => $job->code]),
                [
                    'job_id' => $jobId,
                    'job_code' => $job->code,
                    'quotation_id' => $quotation->id,
                ]
            );

            $this->commitTransaction();

            return $quotation->load('worker');
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Customer accepts a quotation
     */
    public function acceptQuotation($jobId, $quotationId, $customerId)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR check
        if ($job->customer_id != $customerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        // Check job status
        if (! in_array($job->status, [JobStatusConst::WAITING_FOR_QUOTATION, JobStatusConst::QUOTED])) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Cannot accept quotation in current job status',
                400
            );
        }

        $quotation = $this->quotationRepository->find($quotationId);
        if (! $quotation || $quotation->job_id != $jobId) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Quotation not found', 404);
        }

        if ($quotation->status !== QuotationStatusConst::PENDING) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Quotation is not pending',
                400
            );
        }

        $this->beginTransaction();
        try {
            // Accept the quotation
            $this->quotationRepository->update($quotationId, [
                'status' => QuotationStatusConst::ACCEPTED,
            ]);

            // Reject other quotations
            $this->quotationRepository->rejectOtherQuotations($jobId, $quotationId);

            $originalAmount = $quotation->total_amount;
            $discountAmount = 0.0;
            $finalAmount = $originalAmount;

            if ($job->discount_id) {
                $discount = $this->discountService->showAdmin($job->discount_id);
                if ($discount) {
                    $discountAmount = $this->discountService->calculateDiscount($discount, $originalAmount);
                    $finalAmount = $originalAmount - $discountAmount;
                }
            }

            // Update job with worker and price info
            $this->jobRepository->update($jobId, [
                'worker_id' => $quotation->worker_id,
                'quotation_price' => $quotation->price,
                'platform_fee' => $quotation->platform_fee,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'total_amount' => $finalAmount,
                'status' => JobStatusConst::PENDING_PAYMENT,
            ]);

            // TODO: Create chat room

            // Notify worker
            $this->notificationService->sendNotification(
                $quotation->worker_id,
                NotificationTypeConst::JOB_QUOTATION_ACCEPTED,
                __('notification.job_quotation_accepted.title'),
                __('notification.job_quotation_accepted.body', ['job_code' => $job->code]),
                [
                    'job_id' => $jobId,
                    'job_code' => $job->code,
                    'customer_name' => $job->customer->full_name ?? 'Khách hàng',
                    'price' => (float) $quotation->price,
                    'service_name' => $job->serviceCategory->name ?? 'Dịch vụ',
                ]
            );

            $this->commitTransaction();

            return $this->jobRepository->with(['worker', 'quotations'])->find($jobId);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * List quotations for a job
     */
    public function listQuotations($jobId, $customerId, array $filters = [])
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->customer_id != $customerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        $filters['job_id'] = $jobId;
        $this->quotationRepository->pushCriteria(new SortAndFilterQuotationCriteria($filters));

        // Return collection to maintain compatibility, but sorted/filtered
        return $this->quotationRepository->get();
    }

    /**
     * Customer rejects a quotation
     */
    public function rejectQuotation($jobId, $quotationId, $customerId)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // IDOR check
        if ($job->customer_id != $customerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        $quotation = $this->quotationRepository->find($quotationId);
        if (! $quotation || $quotation->job_id != $jobId) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Quotation not found', 404);
        }

        if ($quotation->status !== QuotationStatusConst::PENDING) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Quotation is not pending',
                400
            );
        }

        $this->beginTransaction();
        try {
            // Reject the quotation
            $this->quotationRepository->update($quotationId, [
                'status' => QuotationStatusConst::REJECTED,
            ]);

            // Notify worker
            $this->notificationService->sendNotification(
                $quotation->worker_id,
                NotificationTypeConst::JOB_QUOTATION_REJECTED,
                __('notification.job_quotation_rejected.title'),
                __('notification.job_quotation_rejected.body', ['job_code' => $job->code]),
                [
                    'job_id' => $jobId,
                    'job_code' => $job->code,
                ]
            );

            $this->commitTransaction();

            return $this->quotationRepository->find($quotationId);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}

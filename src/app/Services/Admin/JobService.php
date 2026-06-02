<?php

namespace App\Services\Admin;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Job\SortAndFilterJobCriteria;
use App\Repositories\Job\JobRepository;
use App\Services\AbstractService;

class JobService extends AbstractService
{
    protected $jobRepository;

    protected $walletService;

    protected $paymentRepository;

    public function __construct(
        JobRepository $jobRepository,
        \App\Services\Wallet\WalletService $walletService,
        \App\Repositories\Payment\PaymentRepository $paymentRepository
    ) {
        $this->jobRepository = $jobRepository;
        $this->walletService = $walletService;
        $this->paymentRepository = $paymentRepository;
    }

    public function listJobs(array $requestData)
    {
        $filters = $requestData['filters'] ?? [];
        $sorts = $requestData['sorts'] ?? [];
        $search = $requestData['search'] ?? [];

        $this->jobRepository->pushCriteria(new SortAndFilterJobCriteria($filters, $sorts, $search));

        return $this->jobRepository->with(['customer', 'worker', 'serviceCategory', 'area'])->paginate($requestData['limit'] ?? 10);
    }

    public function getJobDetail($id)
    {
        $job = $this->jobRepository->with([
            'customer.customerProfile',
            'worker.workerProfile',
            'serviceCategory',
            'area',
            'media',
            'quotations.worker',
            'reviews.reviewer',
            'complaints',
            'invitedWorkers',
            'notes.admin',
        ])->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        return $job;
    }

    public function resolveComplete($id, $adminId)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // Validate status: Must be COMPLAINT
        if ($job->status !== \App\Constants\Master\Models\Job\JobStatusConst::COMPLAINT) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job status not valid for resolution', 400);
        }

        $this->beginTransaction();
        try {
            // Update Job
            $this->jobRepository->update($id, [
                'status' => \App\Constants\Master\Models\Job\JobStatusConst::COMPLETED,
                'updated_by' => $adminId,
            ]);

            // Update Complaint (if exists)
            // Assuming we have a way to find relevant complaint or just update all pending for this job
            // For now, let's skip complaint update detail or assuming listener handles it,
            // but explicitly:
            $job->complaints()->update(['status' => 'resolved', 'resolution_note' => 'Resolved by Admin (Complete Job)', 'resolver_id' => $adminId]);

            // Transfer Money to Worker (if not cash?)
            // Assuming for now we credit worker regardless of payment method implies "Platform pays worker".
            // TODO: Real logic needs to check if payment was online.

            // Trigger Wallet Transfer if payment was not CASH
            $payment = $this->paymentRepository->findWhere([
                'job_id' => $id,
                'status' => \App\Constants\Master\Models\Payment\PaymentStatusConst::PAID,
            ])->first();

            // Default to credit if no payment record found (assuming online/system hold)
            // OR if payment exists and is NOT CASH.
            // If CASH payment exists, worker already has money.
            $shouldCredit = true;
            if ($payment && $payment->payment_method === \App\Constants\Master\Models\Payment\PaymentMethodConst::CASH) {
                $shouldCredit = false;
            }

            if ($shouldCredit) {
                $this->walletService->creditWorker(
                    $job->worker_id,
                    $payment->worker_earning ?? $job->quotation_price,
                    "Job #{$job->code} completed by Admin Resolution",
                    $job->id
                );
            }

            $this->commitTransaction();

            return $job->refresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function resolveRefund($id, $adminId)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        $this->beginTransaction();
        try {
            // Update Job
            $this->jobRepository->update($id, [
                'status' => \App\Constants\Master\Models\Job\JobStatusConst::CANCELLED,
                'cancelled_reason' => 'Admin Resolved: Refund',
                'updated_by' => $adminId,
            ]);

            // Update Complaint
            $job->complaints()->update(['status' => 'resolved', 'resolution_note' => 'Resolved by Admin (Refund)', 'resolver_id' => $adminId]);

            // Refund Customer (If online payment)
            // TODO: Implement Refund Logic

            $this->commitTransaction();

            return $job->refresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function addJobNote($jobId, $adminId, string $note)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        $this->beginTransaction();
        try {
            $jobNote = \App\Models\JobNote::create([
                'job_id' => $jobId,
                'admin_id' => $adminId,
                'note' => $note,
                'created_by' => $adminId,
            ]);

            $this->commitTransaction();

            return $jobNote->load('admin');
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function listJobNotes($jobId)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        return $job->notes()->with('admin')->orderBy('created_at', 'desc')->get();
    }
}

<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Complaint\ComplaintStatusConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Complaint\ComplaintRepository;
use App\Repositories\Criteria\Job\JobVisibilityCriteria;
use App\Repositories\Criteria\Job\SortAndFilterJobCriteria;
use App\Repositories\Job\JobRepository;
use App\Repositories\JobMedia\JobMediaRepository;
use App\Repositories\Quotation\QuotationRepository;
use App\Repositories\UserAddress\UserAddressRepository;
use App\Services\AbstractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class JobService extends AbstractService
{
    protected $jobInvitedWorkerRepository;

    protected $workerProfileRepository;

    protected $jobRepository;

    protected $jobMediaRepository;

    protected $quotationRepository;

    protected $complaintRepository;

    protected $complaintEvidenceRepository;

    protected $imageRepository;

    protected $paymentRepository;

    protected $walletService;

    protected $notificationService;

    protected $configurationService;

    protected $reviewRepository;

    protected $userAddressRepository;

    protected $discountService;

    public function __construct(
        JobRepository $jobRepository,
        JobMediaRepository $jobMediaRepository,
        QuotationRepository $quotationRepository,
        ComplaintRepository $complaintRepository,
        \App\Repositories\ComplaintEvidence\ComplaintEvidenceRepository $complaintEvidenceRepository,
        \App\Repositories\JobInvitedWorker\JobInvitedWorkerRepository $jobInvitedWorkerRepository,
        \App\Repositories\WorkerProfile\WorkerProfileRepository $workerProfileRepository,
        \App\Repositories\Image\ImageRepository $imageRepository,
        \App\Repositories\Payment\PaymentRepository $paymentRepository,
        \App\Services\Wallet\WalletService $walletService,
        NotificationService $notificationService,
        \App\Services\Admin\ConfigurationService $configurationService,
        \App\Repositories\Review\ReviewRepository $reviewRepository,
        UserAddressRepository $userAddressRepository,
        DiscountService $discountService
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobMediaRepository = $jobMediaRepository;
        $this->quotationRepository = $quotationRepository;
        $this->complaintRepository = $complaintRepository;
        $this->complaintEvidenceRepository = $complaintEvidenceRepository;
        $this->jobInvitedWorkerRepository = $jobInvitedWorkerRepository;
        $this->workerProfileRepository = $workerProfileRepository;
        $this->imageRepository = $imageRepository;
        $this->paymentRepository = $paymentRepository;
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
        $this->configurationService = $configurationService;
        $this->reviewRepository = $reviewRepository;
        $this->userAddressRepository = $userAddressRepository;
        $this->discountService = $discountService;
    }

    public function createJob(array $data, array $mediaCodes, \App\Models\User $user)
    {
        // Check customer profile completeness (precondition)
        $profile = $user->customerProfile;
        if (! $profile || ! $profile->phone || ! $profile->area_id) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::PROFILE_INCOMPLETE,
                'Please complete your profile (phone, address) before creating a job',
                403
            );
        }

        // 1. Address Book logic & IDOR protection
        if (! empty($data['user_address_id'])) {
            $address = $this->userAddressRepository->find($data['user_address_id']);
            if (! $address) {
                throw new \App\Exceptions\BusinessException(
                    \App\Constants\Commons\ExceptionCode::NOT_FOUND,
                    'Address not found',
                    404
                );
            }
            if ($address->user_id !== $user->id) {
                throw new \App\Exceptions\BusinessException(
                    \App\Constants\Commons\ExceptionCode::PERMISSION_DENIED,
                    'Unauthorized address selection',
                    403
                );
            }
            // Auto mapping details
            $data['area_id'] = $address->area_id;
            $data['address'] = $address->address_detail;
            $data['latitude'] = $address->latitude;
            $data['longitude'] = $address->longitude;
        }

        // 2. Booking Time Slot logic
        $workTimeType = $data['work_time_type'] ?? null;
        if ($workTimeType === 'MORNING') {
            $data['work_start_time'] = '08:00';
            $data['work_end_time'] = '11:30';
        } elseif ($workTimeType === 'AFTERNOON') {
            $data['work_start_time'] = '13:30';
            $data['work_end_time'] = '17:00';
        } elseif ($workTimeType === 'EVENING') {
            $data['work_start_time'] = '18:00';
            $data['work_end_time'] = '21:00';
        }

        // Backward compatibility for time_slot string
        if (! empty($data['work_start_time']) && ! empty($data['work_end_time'])) {
            $start = substr($data['work_start_time'], 0, 5);
            $end = substr($data['work_end_time'], 0, 5);
            $data['time_slot'] = "{$start}-{$end}";
        }

        $this->beginTransaction();
        try {
            // Apply voucher discount if code is provided
            if (! empty($data['discount_code'])) {
                $discount = $this->discountService->validateVoucher($data['discount_code'], null, $user->id);
                $data['discount_id'] = $discount->id;
                $data['discount_code'] = $discount->code;
            }

            $data['customer_id'] = $user->id;
            $data['status'] = JobStatusConst::WAITING_FOR_QUOTATION;

            $job = $this->jobRepository->create($data);

            if (! empty($data['discount_code']) && isset($discount)) {
                $discount->increment('used_quantity');
            }

            if (! empty($mediaCodes)) {
                $images = $this->imageRepository->findWhereIn('code', $mediaCodes)->all();
                foreach ($images as $image) {
                    $type = in_array(strtolower($image->extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';

                    $this->jobMediaRepository->create([
                        'job_id' => $job->id,
                        'url' => $image->url,
                        'type' => $type,
                        'created_by' => $user->code,
                    ]);
                }
            }

            $this->commitTransaction();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        // Distribute job to workers AFTER commit (don't block job creation)
        try {
            $this->distributeJob($job);
        } catch (\Throwable $e) {
            Log::error('Failed to distribute job #'.$job->id.': '.$e->getMessage(), [
                'job_id' => $job->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $job;
    }

    public function listCustomerJobs($userId, array $requestData)
    {
        $filters = $requestData['filters'] ?? [];
        $sorts = $requestData['sorts'] ?? [];
        $search = $requestData['search'] ?? [];
        $tab = $requestData['tab'] ?? null;

        $filters['customer_id'] = $userId;

        if ($tab) {
            $tabStatuses = null;
            switch ($tab) {
                case 'active':
                    $tabStatuses = \App\Constants\Master\Models\Job\JobStatusConst::customerActiveStatuses();
                    break;
                case 'history':
                    $tabStatuses = \App\Constants\Master\Models\Job\JobStatusConst::customerHistoryStatuses();
                    break;
                case 'requesting':
                    $tabStatuses = \App\Constants\Master\Models\Job\JobStatusConst::customerRequestingStatuses();
                    break;
                case 'in_progress':
                    $tabStatuses = \App\Constants\Master\Models\Job\JobStatusConst::customerInProgressStatuses();
                    break;
                case 'completed':
                    $tabStatuses = \App\Constants\Master\Models\Job\JobStatusConst::completedStatuses();
                    break;
            }

            if ($tabStatuses) {
                unset($filters['status']);
                $filters['status_in'] = $tabStatuses;
            }
        }

        $this->jobRepository->pushCriteria(new SortAndFilterJobCriteria($filters, $sorts, $search));

        return $this->jobRepository->with(['serviceCategory.parent', 'area', 'media', 'quotations', 'worker.workerProfile'])->paginate($requestData['limit'] ?? 10);
    }

    public function getJobDetail($id, $userId)
    {
        $job = $this->jobRepository->with(['media', 'serviceCategory.parent', 'area', 'quotations', 'worker.workerProfile', 'reviews' => function ($q) use ($userId) {
            $q->where('reviewer_id', $userId);
        }])->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // Check ownership (only customer or worker related to job can view?)
        // Spec doesn't strictly say, but usually yes.
        // For now, allow customer who owns it.
        if ($job->customer_id != $userId) {
            // Check if worker is related? For now just strict ownership for customer endpoint.
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized access to this job', 403);
        }

        return $job;
    }

    public function cancelJob($id, $userId, $reason)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->customer_id != $userId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        if ($job->status !== JobStatusConst::WAITING_FOR_QUOTATION) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job cannot be cancelled in this status', 400);
        }

        $this->beginTransaction();
        try {
            $result = $this->jobRepository->update($id, [
                'status' => JobStatusConst::CANCELLED,
                'cancelled_reason' => $reason,
                'updated_by' => $userId,
            ]);

            $this->commitTransaction();

            return $result;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    private function getMediaType(UploadedFile $file)
    {
        $mime = $file->getMimeType();
        if (str_contains($mime, 'image')) {
            return 'image';
        }
        if (str_contains($mime, 'video')) {
            return 'video';
        }

        return 'other';
    }

    // =====================
    // Worker Methods
    // =====================

    /**
     * List available jobs for a worker (matching services/areas, not yet quoted)
     */
    public function listAvailableJobs($worker, array $requestData)
    {
        $filters = $requestData['filters'] ?? [];
        $sorts = $requestData['sorts'] ?? [];
        $type = $requestData['type'] ?? null;

        // Get worker's services and areas
        $workerServiceIds = $worker->workerProfile?->services?->pluck('service_category_id')->toArray() ?? [];
        $workerAreaIds = $worker->workerProfile?->areas?->pluck('area_id')->toArray() ?? [];

        // Get jobs worker already quoted
        $quotedJobIds = $this->quotationRepository->getQuotedJobIdsByWorker($worker->id);

        // Inject listing constraints into filters
        $filters['status'] = JobStatusConst::WAITING_FOR_QUOTATION;
        $filters['service_ids'] = $workerServiceIds;
        $filters['area_ids'] = $workerAreaIds;
        $filters['exclude_ids'] = $quotedJobIds;

        $this->jobRepository->pushCriteria(new SortAndFilterJobCriteria($filters, $sorts));

        // Visibility criteria for Open vs Invited jobs
        $this->jobRepository->pushCriteria(new JobVisibilityCriteria($worker, $type));

        return $this->jobRepository->with(['serviceCategory.parent', 'area', 'media', 'invitedWorkers'])->paginate($requestData['limit'] ?? 10);
    }

    /**
     * List worker's jobs (quoted or assigned)
     */
    public function listWorkerJobs($workerId, array $requestData)
    {
        $filters = $requestData['filters'] ?? [];
        $sorts = $requestData['sorts'] ?? [];
        $search = $requestData['search'] ?? [];
        $tab = $requestData['tab'] ?? null;
        $type = $requestData['type'] ?? null;

        $filters['worker_id'] = $workerId;
        $filters['worker_job_type'] = $type ?? 'all';

        if ($tab) {
            $tabStatuses = null;
            switch ($tab) {
                case 'in_progress':
                case 'completed':
                    $filters['worker_list_tab'] = $tab;
                    unset($filters['status']);
                    break;
            }
        }

        $this->jobRepository->pushCriteria(new SortAndFilterJobCriteria($filters, $sorts, $search));

        return $this->jobRepository->with(['serviceCategory.parent', 'area', 'customer', 'quotations', 'invitedWorkers'])->paginate($requestData['limit'] ?? 10);
    }

    /**
     * Get job detail for worker
     */
    public function getWorkerJobDetail($id, $workerId)
    {
        $job = $this->jobRepository->with(['media', 'serviceCategory.parent', 'area', 'customer', 'quotations', 'invitedWorkers', 'reviews' => function ($q) use ($workerId) {
            $q->where('reviewer_id', $workerId);
        }])->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        // Access rules:
        // 1. Worker is assigned to this job
        // 2. Worker has already quoted
        // 3. Worker is invited to this job
        // 4. Job is open (waiting_for_quotation + no invited workers)
        $isAssigned = $job->worker_id == $workerId;
        $hasQuoted = $job->quotations->where('worker_id', $workerId)->count() > 0;
        $isInvited = $job->invitedWorkers->where('id', $workerId)->count() > 0;
        $isOpenJob = $job->status === JobStatusConst::WAITING_FOR_QUOTATION && $job->invitedWorkers->isEmpty();

        if (! $isAssigned && ! $hasQuoted && ! $isInvited && ! $isOpenJob) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You do not have access to this job', 403);
        }

        return $job;
    }

    /**
     * Worker starts the job
     */
    public function startJob($id, $workerId)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->worker_id != $workerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You are not assigned to this job', 403);
        }

        if ($job->status !== JobStatusConst::PAID) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Job must be in paid status to start',
                400
            );
        }

        $this->beginTransaction();
        try {
            $this->jobRepository->update($id, [
                'status' => JobStatusConst::IN_PROGRESS,
                'started_at' => now(),
                'updated_by' => $workerId,
            ]);

            // Notify customer
            $this->notificationService->sendNotification(
                $job->customer_id,
                \App\Constants\Master\Models\Notification\NotificationTypeConst::JOB_STARTED,
                __('notification.job_started.title'),
                __('notification.job_started.body', ['job_code' => $job->code]),
                [
                    'job_id' => $job->id,
                    'job_code' => $job->code,
                ]
            );

            $this->commitTransaction();

            return $this->jobRepository->find($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Worker marks job as complete → status directly becomes COMPLETED.
     * Payment is released to worker wallet.
     */
    public function completeJob($id, $workerId)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->worker_id != $workerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You are not assigned to this job', 403);
        }

        if ($job->status !== JobStatusConst::IN_PROGRESS) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Job must be in progress to complete',
                400
            );
        }

        $this->beginTransaction();
        try {
            $this->jobRepository->update($id, [
                'status' => JobStatusConst::COMPLETED,
                'completed_at' => now(),
                'updated_by' => $workerId,
            ]);

            // Release payment to worker wallet
            $payment = $this->paymentRepository->findWhere([
                'job_id' => $id,
                'status' => PaymentStatusConst::PAID,
            ])->first();

            if ($payment && $payment->payment_method !== PaymentMethodConst::CASH) {
                $this->walletService->creditWorkerEscrow(
                    $job->worker_id,
                    $payment->worker_earning ?? $job->quotation_price,
                    "Job #{$job->code} completed (Escrow)",
                    $job->id,
                    3 // 3 days escrow
                );
            } elseif ($payment && $payment->payment_method === PaymentMethodConst::CASH) {
                // For cash payment:
                // 1. Record the earning (for statistics)
                $this->walletService->recordCashEarning(
                    $job->worker_id,
                    $payment->worker_earning ?? $job->quotation_price,
                    "Cash earning from Job #{$job->code}",
                    $job->id
                );

                // 2. Debit the platform fee from worker wallet
                if ($payment->platform_fee > 0) {
                    $this->walletService->debitFee(
                        $job->worker_id,
                        $payment->platform_fee,
                        "Platform fee for Job #{$job->code} (Cash)",
                        $job->id
                    );
                }
            }

            // Notify customer
            $this->notificationService->sendNotification(
                $job->customer_id,
                \App\Constants\Master\Models\Notification\NotificationTypeConst::JOB_COMPLETED,
                __('notification.job_completed.title'),
                __('notification.job_completed.body', ['job_code' => $job->code]),
                [
                    'job_id' => $job->id,
                    'job_code' => $job->code,
                ]
            );

            $this->commitTransaction();

            return $this->jobRepository->find($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Distribute job to qualified workers using geo-distance ranking.
     *
     * Strategy:
     * 1. If job has lat/lng → Bounding Box pre-filter + Haversine distance sort
     * 2. Fallback → area_id match (for workers without lat/lng or jobs without lat/lng)
     * 3. No candidates → broadcast to ALL workers (open job)
     */
    public function distributeJob($job)
    {
        $config = $this->configurationService->getJobAssignmentConfig();
        $maxWorkers = $config['max_workers_per_job'] ?? 5;
        $scanRadius = $config['scan_radius'] ?? 10; // km

        $candidates = collect();

        // Strategy 1: Geo-distance search (if job has coordinates)
        if ($job->latitude && $job->longitude && $job->service_id) {
            $candidates = $this->findWorkersByGeoDistance(
                $job->latitude,
                $job->longitude,
                $job->service_id,
                $job->time_slot,
                $scanRadius
            );
            Log::info("distributeJob #{$job->id}: Geo search found {$candidates->count()} candidates");
        }

        // Strategy 2: Area-based fallback (if not enough geo candidates)
        if ($candidates->count() < $maxWorkers && $job->service_id && $job->area_id) {
            $excludeUserIds = $candidates->pluck('user_id')->toArray();
            $areaFallback = $this->findWorkersByArea(
                $job->service_id,
                $job->area_id,
                $job->time_slot,
                $excludeUserIds
            );
            $candidates = $candidates->merge($areaFallback);
            Log::info("distributeJob #{$job->id}: Area fallback found {$areaFallback->count()} more, total {$candidates->count()}");
        }

        // Take top N
        $topCandidates = $candidates->take($maxWorkers);

        if ($topCandidates->isEmpty()) {
            // No matching workers — job stays as public (no entries in t_job_invited_workers)
            // All workers can see and quote on this job
            Log::info("distributeJob #{$job->id}: No candidates found, job is public for all workers");

            return;
        }

        // Invite selected workers
        Log::info("distributeJob #{$job->id}: Inviting {$topCandidates->count()} workers");
        $invitations = [];
        foreach ($topCandidates as $candidate) {
            $invitations[] = [
                'job_id' => $job->id,
                'worker_id' => $candidate->user->id,
                'status' => \App\Constants\Master\Models\JobInvitedWorker\JobInvitedWorkerStatusConst::ASSIGNED,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->jobInvitedWorkerRepository->insertInvitations($invitations);

        foreach ($topCandidates as $candidate) {
            $workerId = $candidate->user->id;
            
            $serviceName = $job->serviceCategory->name ?? 'Dịch vụ';
            $districtName = $job->area->name ?? '';
            $distance = isset($candidate->distance_km) ? round((float)$candidate->distance_km, 1) : null;
            
            $budgetMin = 300000;
            $budgetMax = 500000;
            if ($job->total_amount > 0) {
                $budgetMin = (int) ($job->total_amount * 0.9);
                $budgetMax = (int) ($job->total_amount * 1.1);
            }
            
            $scheduleText = "ngày " . ($job->scheduled_date ? $job->scheduled_date->format('d/m/Y') : date('d/m/Y'));

            try {
                $this->notificationService->sendNotification(
                    $workerId,
                    \App\Constants\Master\Models\Notification\NotificationTypeConst::NEW_JOB_NEARBY,
                    __('notification.new_job_nearby.title'),
                    __('notification.new_job_nearby.body', ['service_name' => $serviceName, 'distance' => $distance ?? 'gần']),
                    [
                        'job_id' => $job->id,
                        'job_code' => $job->code,
                        'title' => $serviceName,
                        'distance' => $distance,
                        'district' => $districtName,
                        'schedule' => $scheduleText,
                        'budget_min' => $budgetMin,
                        'budget_max' => $budgetMax,
                        'is_hot' => true
                    ]
                );
            } catch (\Throwable $ne) {
                Log::error('New Job Nearby Notification Error: ' . $ne->getMessage());
            }
        }
    }

    /**
     * Find workers by geo-distance using Bounding Box pre-filter + Haversine.
     */
    private function findWorkersByGeoDistance(float $lat, float $lng, int $serviceId, ?string $timeSlot, float $radiusKm)
    {
        // Bounding Box delta (1 degree ≈ 111km)
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        $haversine = '(6371 * acos(
            LEAST(1, cos(radians(?)) * cos(radians(m_worker_profiles.latitude))
            * cos(radians(m_worker_profiles.longitude) - radians(?))
            + sin(radians(?)) * sin(radians(m_worker_profiles.latitude)))
        ))';

        $query = $this->workerProfileRepository->getInstance()->newQuery()
            ->selectRaw("m_worker_profiles.*, {$haversine} AS distance_km", [$lat, $lng, $lat]);

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->where('availability', true)
            ->where('activity_status', \App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus::ACTIVE)
            ->whereHas('user', function ($q) {
                $q->where('status', \App\Constants\Master\Models\User\UserStatusConst::ACTIVE);
            })
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('service_category_id', $serviceId);
            });

        // Only filter by time_slot if provided
        if ($timeSlot) {
            $query->where(function ($q) use ($timeSlot) {
                $q->whereHas('timeSlots', function ($sq) use ($timeSlot) {
                    $sq->where('time_slot', $timeSlot);
                })->orWhereDoesntHave('timeSlots');
            });
        }

        return $query
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->orderByDesc('avg_rating')
            ->orderByDesc('total_completed_jobs')
            ->with('user')
            ->get();
    }

    /**
     * Find workers by area_id match (fallback for workers without geo coordinates).
     */
    private function findWorkersByArea(int $serviceId, int $areaId, ?string $timeSlot, array $excludeUserIds = [])
    {
        $query = $this->workerProfileRepository->getInstance()->newQuery()
            ->whereHas('user', function ($q) use ($excludeUserIds) {
                $q->where('status', \App\Constants\Master\Models\User\UserStatusConst::ACTIVE);
                if (! empty($excludeUserIds)) {
                    $q->whereNotIn('id', $excludeUserIds);
                }
            })
            ->where('availability', true)
            ->where('activity_status', \App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus::ACTIVE)
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('service_category_id', $serviceId);
            })
            ->whereHas('areas', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });

        // Only filter by time_slot if provided
        if ($timeSlot) {
            $query->where(function ($q) use ($timeSlot) {
                $q->whereHas('timeSlots', function ($sq) use ($timeSlot) {
                    $sq->where('time_slot', $timeSlot);
                })->orWhereDoesntHave('timeSlots');
            });
        }

        return $query
            ->orderByDesc('avg_rating')
            ->orderByDesc('total_completed_jobs')
            ->with('user')
            ->get();
    }

    /**
     * Customer submits a complaint
     */
    public function submitComplaint($jobId, array $data, array $mediaCodes, $customer)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->customer_id != $customer->id) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Unauthorized', 403);
        }

        // Allow complaint only if job is completed
        if ($job->status !== JobStatusConst::COMPLETED) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Job status invalid for complaint',
                400
            );
        }

        $this->beginTransaction();
        try {
            // Create Complaint
            $complaint = $this->complaintRepository->create([
                'job_id' => $jobId,
                'description' => $data['content'] ?? ($data['description'] ?? null),
                'status' => ComplaintStatusConst::PENDING,
                'created_by' => $customer->id,
            ]);

            if (! empty($mediaCodes)) {
                $images = \App\Models\Image::whereIn('code', $mediaCodes)->get();
                foreach ($images as $image) {
                    $type = in_array(strtolower($image->extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';
                    $this->complaintEvidenceRepository->create([
                        'complaint_id' => $complaint->id,
                        'file_url' => $image->url,
                        'type' => $type,
                        'uploader_id' => $customer->id,
                        'created_by' => $customer->id,
                    ]);
                }
            }

            // Update Job Status
            $this->jobRepository->update($jobId, [
                'status' => JobStatusConst::COMPLAINT,
                'updated_by' => $customer->id,
            ]);

            // Notify Worker
            $this->notificationService->sendNotification(
                $job->worker_id,
                \App\Constants\Master\Models\Notification\NotificationTypeConst::JOB_COMPLAINT,
                __('notification.job_complaint.title'),
                __('notification.job_complaint.body', ['job_code' => $job->code]),
                [
                    'job_id' => $jobId,
                    'job_code' => $job->code,
                    'complaint_id' => $complaint->id,
                ]
            );

            $this->commitTransaction();

            return $complaint;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Worker rejects a job
     */
    public function rejectJob($id, $workerId)
    {
        $job = $this->jobRepository->find($id);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->status !== JobStatusConst::WAITING_FOR_QUOTATION) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Cannot reject job in current status', 400);
        }

        $invitation = $this->jobInvitedWorkerRepository->findWhere([
            'job_id' => $id,
            'worker_id' => $workerId,
        ])->first();

        // Also check if already quoted
        $hasQuoted = $this->quotationRepository->findWhere([
            'job_id' => $id,
            'worker_id' => $workerId,
        ])->first();

        if ($hasQuoted || ($invitation && $invitation->status === \App\Models\JobInvitedWorker::STATUS_REJECTED)) {
            throw new BusinessException(ExceptionCode::DUPLICATE_ENTRY, 'Worker must not have quoted or rejected this job already', 409);
        }

        $this->beginTransaction();
        try {
            if ($invitation) {
                $this->jobInvitedWorkerRepository->update($invitation->id, [
                    'status' => \App\Models\JobInvitedWorker::STATUS_REJECTED,
                ]);
            } else {
                $this->jobInvitedWorkerRepository->insertInvitations([
                    [
                        'job_id' => $id,
                        'worker_id' => $workerId,
                        'status' => \App\Models\JobInvitedWorker::STATUS_REJECTED,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }

            $this->commitTransaction();

            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Worker replies to a complaint
     */
    public function replyComplaint($jobId, $complaintId, array $data, array $mediaCodes, $workerId)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->worker_id != $workerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You do not have access to this complaint', 403);
        }

        $complaint = $this->complaintRepository->find($complaintId);

        if (! $complaint || $complaint->job_id != $jobId) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Complaint not found for this job', 404);
        }

        if (! empty($complaint->worker_note)) {
            throw new BusinessException(ExceptionCode::DUPLICATE_ENTRY, 'You have already replied to this complaint', 409);
        }

        $this->beginTransaction();
        try {
            $this->complaintRepository->update($complaintId, [
                'worker_note' => $data['note'] ?? null,
                'worker_replied_at' => now(),
            ]);

            if (! empty($mediaCodes)) {
                $images = \App\Models\Image::whereIn('code', $mediaCodes)->get();
                foreach ($images as $image) {
                    $type = in_array(strtolower($image->extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';
                    $this->complaintEvidenceRepository->create([
                        'complaint_id' => $complaintId,
                        'file_url' => $image->url,
                        'type' => $type,
                        'uploader_id' => $workerId,
                        'created_by' => $workerId,
                    ]);
                }
            }

            $this->commitTransaction();

            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Customer reviews worker
     */
    public function reviewWorker($jobId, array $data, $customerId)
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Job not found', 404);
        }

        if ($job->customer_id != $customerId) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You do not have permission', 403);
        }

        // According to origin spec, review is handled when job is COMPLETED (or REFUNDED)
        if (! in_array($job->status, [JobStatusConst::COMPLETED, \App\Constants\Master\Models\Job\JobStatusConst::REFUNDED])) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Job status invalid for reviewing', 400);
        }

        if (! $job->worker_id) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Worker not assigned to this job', 400);
        }

        $exists = $this->reviewRepository->findWhere([
            'job_id' => $jobId,
            'reviewer_id' => $customerId,
            'target_id' => $job->worker_id,
        ])->first();

        if ($exists) {
            throw new BusinessException(ExceptionCode::DUPLICATE_ENTRY, 'You have already reviewed this job', 409);
        }

        $this->beginTransaction();
        try {
            $review = $this->reviewRepository->create([
                'job_id' => $jobId,
                'reviewer_id' => $customerId,
                'target_id' => $job->worker_id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'created_by' => $customerId,
            ]);

            // Update worker's average rating in worker profile
            $profile = $this->workerProfileRepository->findWhere(['user_id' => $job->worker_id])->first();
            if ($profile) {
                $avgRating = \App\Models\Review::where('target_id', $job->worker_id)->avg('rating');

                $this->workerProfileRepository->update($profile->id, [
                    'avg_rating' => $avgRating ?? 0,
                ]);
            }

            // Send RATING_RECEIVED notification to worker
            try {
                $reviewer = \App\Models\User::find($customerId);
                $reviewerName = $reviewer->name ?? 'Khách hàng';

                $this->notificationService->sendNotification(
                    $job->worker_id,
                    \App\Constants\Master\Models\Notification\NotificationTypeConst::RATING_RECEIVED,
                    'You received a rating',
                    "{$reviewerName} rated you {$data['rating']} stars.",
                    [
                        'reviewer_name' => $reviewerName,
                        'rating' => (int) $data['rating'],
                        'comment' => $data['comment'] ?? ''
                    ]
                );
            } catch (\Throwable $ne) {
                Log::error('Rating Notification Error: ' . $ne->getMessage());
            }

            $this->commitTransaction();

            return $review;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Cancel all expired jobs (Pending and past schedule time by 30 mins)
     */
    public function cancelExpiredJobs(): int
    {
        $threshold = now()->subMinutes(30);

        $jobs = $this->jobRepository->getInstance()
            ->where('status', JobStatusConst::WAITING_FOR_QUOTATION)
            ->where(function ($query) use ($threshold) {
                $query->where('scheduled_date', '<', $threshold->toDateString())
                    ->orWhere(function ($q) use ($threshold) {
                        $q->where('scheduled_date', $threshold->toDateString())
                            ->where('work_start_time', '<', $threshold->toTimeString());
                    });
            })
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            $this->beginTransaction();
            try {
                $this->jobRepository->update($job->id, [
                    'status' => JobStatusConst::EXPIRED,
                    'updated_by' => 'SYSTEM_CRON',
                ]);

                // Send Push Notification to Customer
                $this->notificationService->sendNotification(
                    $job->customer_id,
                    \App\Constants\Master\Models\Notification\NotificationTypeConst::SYSTEM,
                    'Yêu cầu công việc hết hạn',
                    'Rất tiếc, hiện tại không có thợ nào ở gần nhận yêu cầu của bạn. Vui lòng đặt lại lịch mới nhé!',
                    ['job_id' => $job->id]
                );

                $this->commitTransaction();
                $count++;
            } catch (\Throwable $e) {
                $this->rollbackTransaction();
                Log::error("Failed to cancel expired job #{$job->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}

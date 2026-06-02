<?php

namespace App\Services\Admin;

use App\Constants\Commons\App;
use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Exceptions\BusinessException;
use App\Models\WorkerArea;
use App\Models\WorkerService;
use App\Repositories\Criteria\Master\Worker\SortAndFilterWorkerCriteria;
use App\Repositories\User\UserRepository;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\Common\BaseAuthService;
use App\Services\User\WorkerRegistrationService;

class AdminWorkerService extends BaseAuthService
{
    protected $repository;

    protected $workerProfileRepository;

    protected $notificationService;

    public function __construct(
        UserRepository $userRepository,
        WorkerProfileRepository $workerProfileRepository,
        \App\Services\User\NotificationService $notificationService
    ) {
        $this->repository = $userRepository;
        $this->workerProfileRepository = $workerProfileRepository;
        $this->notificationService = $notificationService;
    }

    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_ADMIN;
    }

    public function guard()
    {
        return auth()->guard($this->guardName());
    }

    /**
     * List workers using Criteria pattern
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $params)
    {
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];
        $limit = $params['limit'] ?? App::PER_PAGE;

        return $this->repository->pushCriteria(
            new SortAndFilterWorkerCriteria($filters, $sorts, $search)
        )->paginate($limit);
    }

    /**
     * Show worker detail
     *
     * @return mixed
     */
    public function show(int $id)
    {
        $user = $this->repository->with([
            'workerProfile',
            'workerProfile.services.serviceCategory.parent',
            'workerProfile.areas.area.parent',
            'workerProfile.selfie',
            'workerProfile.idCardFront',
            'workerProfile.idCardBack',
        ])->find($id);

        if (! $user || $user->role !== UserRoleConst::WORKER) {
            throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Worker not found', 404);
        }

        return $user;
    }

    /**
     * Update worker profile by Admin
     *
     * @return mixed
     */
    public function update(int $id, array $data)
    {
        $user = $this->show($id);
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        $this->beginTransaction();
        try {
            // Update User table (name only)
            if (isset($data['name'])) {
                $this->repository->update($user->id, [
                    'name' => $data['name'],
                ]);
            }

            // Update WorkerProfile table
            $profileData = array_filter([
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'skill_description' => $data['skill_description'] ?? null,
            ], fn ($value) => $value !== null);

            if (! empty($profileData)) {
                $this->workerProfileRepository->update($profile->id, $profileData);
            }

            // Sync services
            if (isset($data['service_ids'])) {
                WorkerService::where('worker_profile_id', $profile->id)->delete();
                foreach ($data['service_ids'] as $serviceId) {
                    WorkerService::create([
                        'worker_profile_id' => $profile->id,
                        'service_category_id' => $serviceId,
                    ]);
                }
            }

            // Sync areas
            if (isset($data['area_ids'])) {
                WorkerArea::where('worker_profile_id', $profile->id)->delete();
                foreach ($data['area_ids'] as $areaId) {
                    WorkerArea::create([
                        'worker_profile_id' => $profile->id,
                        'area_id' => $areaId,
                    ]);
                }
            }

            $this->commitTransaction();

            return $this->show($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Approve worker
     *
     * @return mixed
     */
    public function approve(int $id)
    {
        $user = $this->show($id); // Validates existence and role
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        if ($profile->profile_status === WorkerProfileStatus::APPROVED) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Worker already approved', 400);
        }

        $this->beginTransaction();
        try {
            // Activate user account
            $this->repository->update($user->id, [
                'status' => UserStatusConst::ACTIVE,
            ]);

            $this->workerProfileRepository->update($profile->id, [
                'profile_status' => WorkerProfileStatus::APPROVED,
                'activity_status' => WorkerActivityStatus::ACTIVE,
                'approved_at' => now(),
                'rejection_reason' => null, // Clear if rejected before
            ]);

            // Record history
            $profile->load(['services', 'areas']);
            WorkerRegistrationService::recordAdminAction(
                $profile, 'approved', auth('admin')->id()
            );

            // Send WORKER_PROFILE_APPROVED notification to worker
            try {
                $this->notificationService->sendNotification(
                    $user->id,
                    \App\Constants\Master\Models\Notification\NotificationTypeConst::WORKER_PROFILE_APPROVED,
                    'Worker Profile Approved',
                    'Congratulations! Your worker profile has been approved by the Administrator. You can start accepting jobs now.',
                    []
                );
            } catch (\Throwable $ne) {
                \Illuminate\Support\Facades\Log::error('Worker Profile Approved Notification Error: ' . $ne->getMessage());
            }

            $this->commitTransaction();

            return $this->show($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Reject worker
     *
     * @return mixed
     */
    public function reject(int $id, string $reason)
    {
        $user = $this->show($id);
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        if ($profile->profile_status === WorkerProfileStatus::REJECTED) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Worker already rejected', 400);
        }

        $this->beginTransaction();
        try {
            $this->workerProfileRepository->update($profile->id, [
                'profile_status' => WorkerProfileStatus::REJECTED,
                'rejection_reason' => $reason,
            ]);

            // Record history
            $profile->load(['services', 'areas']);
            WorkerRegistrationService::recordAdminAction(
                $profile, 'rejected', auth('admin')->id(), $reason
            );

            $this->commitTransaction();

            return $this->show($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Suspend worker
     *
     * @return mixed
     */
    public function suspend(int $id, string $reason)
    {
        $user = $this->show($id);
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        if ($profile->activity_status === WorkerActivityStatus::SUSPENDED) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Worker already suspended', 400);
        }

        $this->beginTransaction();
        try {
            $this->workerProfileRepository->update($profile->id, [
                'activity_status' => WorkerActivityStatus::SUSPENDED,
                'suspend_reason' => $reason,
            ]);

            $this->commitTransaction();

            return $this->show($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Activate worker
     *
     * @return mixed
     */
    public function activate(int $id)
    {
        $user = $this->show($id);
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        if ($profile->activity_status === WorkerActivityStatus::ACTIVE) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Worker already active', 400);
        }

        $this->beginTransaction();
        try {
            $this->workerProfileRepository->update($profile->id, [
                'activity_status' => WorkerActivityStatus::ACTIVE,
            ]);

            $this->commitTransaction();

            return $this->show($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get registration history for a worker
     */
    public function getRegistrationHistory(int $id)
    {
        $user = $this->show($id);
        $profile = $user->workerProfile;

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        return $profile->registrationHistories()
            ->with(['actionByAdmin', 'selfie', 'idCardFront', 'idCardBack'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

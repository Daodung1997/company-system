<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Models\Job;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\AbstractService;
use Illuminate\Database\Eloquent\Model;

class WorkerProfileService extends AbstractService
{
    protected $repository;

    public function __construct(WorkerProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getProfile(int $userId)
    {
        return $this->repository->with(['user', 'avatar', 'areas.area.parent', 'services.serviceCategory'])
            ->findWhere(['user_id' => $userId])->first();
    }

    /**
     * Get public worker profile for customer view.
     * Access control: customer must have relationship with worker (quoted/assigned/invited).
     *
     * @param  int  $workerId  The worker's user_id (not worker_profile.id)
     */
    public function getPublicProfile(int $workerId, int $customerId)
    {
        $profile = $this->repository->with(['user', 'avatar', 'services.serviceCategory', 'areas.area.parent'])
            ->findWhere(['user_id' => $workerId])->first();

        if (! $profile) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Worker profile not found', 404);
        }

        // Check if customer has relationship with this worker
        $hasRelation = Job::where('customer_id', $customerId)
            ->where(function ($q) use ($workerId) {
                $q->where('worker_id', $workerId)
                    ->orWhereHas('quotations', fn ($q) => $q->where('worker_id', $workerId))
                    ->orWhereHas('invitedWorkers', fn ($q) => $q->where('worker_id', $workerId));
            })->exists();

        if (! $hasRelation) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'You cannot view this worker profile', 403);
        }

        return $profile;
    }

    public function updateProfile(int $userId, array $data): Model
    {
        $this->beginTransaction();
        try {
            $profile = $this->repository->findWhere(['user_id' => $userId])->first();

            if (! $profile) {
                throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Worker profile not found', 404);
            }

            // Extract time_slots before updating profile fields
            $timeSlots = null;
            if (array_key_exists('time_slots', $data)) {
                $timeSlots = $data['time_slots'];
                unset($data['time_slots']);
            }

            $updateData = [];
            foreach ([
                'phone',
                'address',
                'avatar_code',
                'experience_years',
                'skill_description',
                'latitude',
                'longitude',
                'certificates',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $this->repository->update($profile->id, $updateData);

            // Sync time slots if provided
            if ($timeSlots !== null) {
                $profile->timeSlots()->delete();
                if (! empty($timeSlots)) {
                    foreach ($timeSlots as $slot) {
                        $profile->timeSlots()->create(['time_slot' => $slot]);
                    }
                }
            }

            $this->commitTransaction();

            return $profile->fresh(['timeSlots']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function toggleAvailability(int $userId, array $data = []): Model
    {
        $this->beginTransaction();
        try {
            $profile = $this->repository->findWhere(['user_id' => $userId])->first();
            if (! $profile) {
                throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Worker profile not found', 404);
            }

            $updateData = ['availability' => ! $profile->availability];

            // Update location if provided
            if (isset($data['latitude']) && isset($data['longitude'])) {
                $updateData['latitude'] = $data['latitude'];
                $updateData['longitude'] = $data['longitude'];
            }

            $this->repository->update($profile->id, $updateData);

            $this->commitTransaction();

            return $profile->fresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function updateAreas(int $userId, array $areaIds): Model
    {
        $this->beginTransaction();
        try {
            $profile = $this->repository->findWhere(['user_id' => $userId])->first();
            if (! $profile) {
                throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Worker profile not found', 404);
            }

            // Sync areas
            // Assuming we want to replace all existing areas with new ones
            // We can delete existing WorkerArea for this profile and create new ones
            // OR use sync() if we had ManyToMany relation setup on BelongsToMany.
            // Currently defined as HasMany WorkerArea. So we delete old and create new.

            $profile->areas()->delete();

            $workerAreas = [];
            foreach ($areaIds as $areaId) {
                $workerAreas[] = [
                    'area_id' => $areaId,
                ];
            }

            if (! empty($workerAreas)) {
                $profile->areas()->createMany($workerAreas);
            }

            $this->commitTransaction();

            return $profile->fresh(['areas.area.parent']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function updateServices(int $userId, array $serviceCategoryIds): Model
    {
        $this->beginTransaction();
        try {
            $profile = $this->repository->findWhere(['user_id' => $userId])->first();
            if (! $profile) {
                throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Worker profile not found', 404);
            }

            // Sync services
            $profile->services()->delete();

            $workerServices = [];
            foreach ($serviceCategoryIds as $serviceId) {
                $workerServices[] = [
                    'service_category_id' => $serviceId,
                ];
            }

            if (! empty($workerServices)) {
                $profile->services()->createMany($workerServices);
            }

            $this->commitTransaction();

            return $profile->fresh(['services.serviceCategory']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}

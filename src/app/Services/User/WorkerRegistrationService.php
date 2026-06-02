<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\WorkerArea;
use App\Models\WorkerProfile;
use App\Models\WorkerRegistrationHistory;
use App\Models\WorkerService;
use App\Repositories\WorkerDocument\WorkerDocumentRepository;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\AbstractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkerRegistrationService extends AbstractService
{
    protected $profileRepository;

    protected $documentRepository;

    public function __construct(
        WorkerProfileRepository $profileRepository,
        WorkerDocumentRepository $documentRepository
    ) {
        $this->profileRepository = $profileRepository;
        $this->documentRepository = $documentRepository;
    }

    /**
     * Submit worker registration (KYC flow)
     */
    /**
     * Submit worker registration (KYC flow)
     */
    public function submitRegistration(array $data): WorkerProfile
    {
        /** @var User $user */
        $user = auth('api')->user();

        // Check if already submitted
        $existingProfile = $this->profileRepository->findWhere(['user_id' => $user->id])->first();
        $allowedStatuses = [WorkerProfileStatus::INCOMPLETE, WorkerProfileStatus::REJECTED];
        if ($existingProfile && ! in_array($existingProfile->profile_status, $allowedStatuses)) {
            throw new BusinessException(
                ExceptionCode::ALREADY_SUBMITTED,
                'Bạn đã gửi hồ sơ trước đó',
                409
            );
        }

        $this->beginTransaction();
        try {
            // Create or update profile
            $profileData = [
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'dob' => $data['dob'],
                'id_card_number' => $data['id_card_number'],
                'id_card_issue_date' => $data['id_card_issue_date'],
                'permanent_address' => $data['permanent_address'],
                'selfie_id' => $data['selfie_id'],
                'id_card_front_id' => $data['id_card_front_id'],
                'id_card_back_id' => $data['id_card_back_id'],
                'gender' => $data['gender'] ?? null,
                'experience_years' => $data['experience_years'],
                'skill_description' => $data['skill_description'],
                'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
                'rejection_reason' => null,
                'activity_status' => WorkerActivityStatus::INACTIVE,
                'availability' => false,
            ];

            if ($existingProfile) {
                $this->profileRepository->update($existingProfile->id, $profileData);
                $profile = $existingProfile->fresh();
                // Clear old services and areas
                WorkerService::where('worker_profile_id', $profile->id)->delete();
                WorkerArea::where('worker_profile_id', $profile->id)->delete();
            } else {
                $profile = $this->profileRepository->create($profileData);
            }

            // Add services
            foreach ($data['service_ids'] as $serviceId) {
                WorkerService::create([
                    'worker_profile_id' => $profile->id,
                    'service_category_id' => $serviceId,
                ]);
            }

            // Add areas
            foreach ($data['area_ids'] as $areaId) {
                WorkerArea::create([
                    'worker_profile_id' => $profile->id,
                    'area_id' => $areaId,
                ]);
            }

            // Update user name if provided
            if (isset($data['name'])) {
                $user->update(['name' => $data['name']]);
            }

            // Record history snapshot
            $this->recordHistory($profile, $data, 'submitted');

            $this->commitTransaction();

            return $profile->load(['services.serviceCategory', 'areas.area.parent']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    /**
     * Get registration status
     */
    public function getRegistrationStatus(): array
    {
        $user = auth('api')->user();
        $profile = $this->profileRepository->findWhere(['user_id' => $user->id])->first();

        if (! $profile) {
            return [
                'profile_status' => 'not_started',
                'rejection_reason' => null,
                'approved_at' => null,
                'submitted_at' => null,
            ];
        }

        return [
            'id' => $profile->id,
            'profile_status' => $profile->profile_status,
            'rejection_reason' => $profile->rejection_reason,
            'approved_at' => $profile->approved_at?->toISOString(),
            'submitted_at' => $profile->created_at?->toISOString(),
        ];
    }

    /**
     * Resubmit registration after rejection
     */
    public function resubmitRegistration(array $data): WorkerProfile
    {
        /** @var User $user */
        $user = auth('api')->user();
        $profile = $this->profileRepository->findWhere(['user_id' => $user->id])->first();

        if (! $profile) {
            throw new BusinessException(
                ExceptionCode::NOT_FOUND,
                'Worker profile not found',
                404
            );
        }

        if ($profile->profile_status !== WorkerProfileStatus::REJECTED) {
            throw new BusinessException(
                ExceptionCode::INVALID_STATUS,
                'Chỉ có thể gửi lại hồ sơ khi bị từ chối',
                409
            );
        }

        $this->beginTransaction();
        try {
            $profileData = [
                'phone' => $data['phone'],
                'dob' => $data['dob'],
                'id_card_number' => $data['id_card_number'],
                'id_card_issue_date' => $data['id_card_issue_date'],
                'permanent_address' => $data['permanent_address'],
                'selfie_id' => $data['selfie_id'],
                'id_card_front_id' => $data['id_card_front_id'],
                'id_card_back_id' => $data['id_card_back_id'],
                'gender' => $data['gender'] ?? $profile->gender,
                'experience_years' => $data['experience_years'],
                'skill_description' => $data['skill_description'],
                'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
                'rejection_reason' => null,
            ];

            $this->profileRepository->update($profile->id, $profileData);

            // Update services
            WorkerService::where('worker_profile_id', $profile->id)->delete();
            foreach ($data['service_ids'] as $serviceId) {
                WorkerService::create([
                    'worker_profile_id' => $profile->id,
                    'service_category_id' => $serviceId,
                ]);
            }

            // Update areas
            WorkerArea::where('worker_profile_id', $profile->id)->delete();
            foreach ($data['area_ids'] as $areaId) {
                WorkerArea::create([
                    'worker_profile_id' => $profile->id,
                    'area_id' => $areaId,
                ]);
            }

            // Update user name if provided
            if (isset($data['name'])) {
                $user->update(['name' => $data['name']]);
            }

            // Record history snapshot
            $this->recordHistory($profile, $data, 'submitted');

            $this->commitTransaction();

            return $profile->fresh()->load(['services.serviceCategory', 'areas.area.parent']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    /**
     * Record registration history snapshot
     */
    protected function recordHistory(WorkerProfile $profile, array $data, string $action, ?int $actionBy = null, ?string $reason = null): void
    {
        WorkerRegistrationHistory::create([
            'worker_profile_id' => $profile->id,
            'attempt_number' => $profile->registrationHistories()
                ->where('action', 'submitted')->count(),
            'phone' => $data['phone'] ?? $profile->phone,
            'dob' => $data['dob'] ?? $profile->dob,
            'id_card_number' => $data['id_card_number'] ?? $profile->id_card_number,
            'id_card_issue_date' => $data['id_card_issue_date'] ?? $profile->id_card_issue_date,
            'permanent_address' => $data['permanent_address'] ?? $profile->permanent_address,
            'selfie_id' => $data['selfie_id'] ?? $profile->selfie_id,
            'id_card_front_id' => $data['id_card_front_id'] ?? $profile->id_card_front_id,
            'id_card_back_id' => $data['id_card_back_id'] ?? $profile->id_card_back_id,
            'gender' => $data['gender'] ?? $profile->gender,
            'experience_years' => $data['experience_years'] ?? $profile->experience_years,
            'skill_description' => $data['skill_description'] ?? $profile->skill_description,
            'service_ids' => $data['service_ids'] ?? null,
            'area_ids' => $data['area_ids'] ?? null,
            'action' => $action,
            'action_by' => $actionBy,
            'action_reason' => $reason,
        ]);
    }

    /**
     * Record admin action (approve/reject) — called from AdminWorkerService
     */
    public static function recordAdminAction(WorkerProfile $profile, string $action, int $adminId, ?string $reason = null): void
    {
        WorkerRegistrationHistory::create([
            'worker_profile_id' => $profile->id,
            'attempt_number' => $profile->registrationHistories()
                ->where('action', 'submitted')->count(),
            'phone' => $profile->phone,
            'dob' => $profile->dob,
            'id_card_number' => $profile->id_card_number,
            'id_card_issue_date' => $profile->id_card_issue_date,
            'permanent_address' => $profile->permanent_address,
            'selfie_id' => $profile->selfie_id,
            'id_card_front_id' => $profile->id_card_front_id,
            'id_card_back_id' => $profile->id_card_back_id,
            'gender' => $profile->gender,
            'experience_years' => $profile->experience_years,
            'skill_description' => $profile->skill_description,
            'service_ids' => $profile->services->pluck('service_category_id')->toArray(),
            'area_ids' => $profile->areas->pluck('area_id')->toArray(),
            'action' => $action,
            'action_by' => $adminId,
            'action_reason' => $reason,
        ]);
    }

    /**
     * Upload file to storage
     */
    protected function uploadFile(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'public');
    }

    /**
     * Handle exceptions - rethrow BusinessException, wrap others
     */
    protected function handleException(\Throwable $e): never
    {
        if ($e instanceof BusinessException) {
            throw $e;
        }

        \Illuminate\Support\Facades\Log::error('WorkerRegistration error: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        throw new BusinessException(
            ExceptionCode::INTERNAL_SERVER_ERROR,
            'An error occurred during registration process',
            500
        );
    }
}

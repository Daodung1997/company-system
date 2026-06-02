<?php

namespace App\Http\Resources\Admin\Worker;

use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'role' => (int) $this->role,
            'status' => (int) $this->status, // User account status
            'phone' => $this->workerProfile?->phone,
            'address' => $this->workerProfile?->address,
            'avatar_url' => $this->workerProfile?->avatar_url,
            'experience_years' => $this->workerProfile?->experience_years,
            'skill_description' => $this->workerProfile?->skill_description,
            'availability' => $this->workerProfile?->availability,
            'rejection_reason' => $this->workerProfile?->rejection_reason,
            'suspend_reason' => $this->workerProfile?->suspend_reason,
            'approved_at' => $this->workerProfile?->approved_at,
            'services' => $this->workerProfile ? WorkerServiceResource::collection($this->workerProfile->services) : [],
            'work_areas' => $this->workerProfile ? WorkerAreaResource::collection($this->workerProfile->areas) : [],
            'kyc_documents' => $this->workerProfile ? [
                'selfie' => $this->workerProfile->selfie ? new ImageSimpleResource($this->workerProfile->selfie) : null,
                'id_card_front' => $this->workerProfile->idCardFront ? new ImageSimpleResource($this->workerProfile->idCardFront) : null,
                'id_card_back' => $this->workerProfile->idCardBack ? new ImageSimpleResource($this->workerProfile->idCardBack) : null,
            ] : null,
            'submission_count' => $this->workerProfile?->registrationHistories()
                ->where('action', 'submitted')->count() ?? 0,
            'stats' => [ // Placeholders, update when real stats are available
                'total_jobs' => $this->workerProfile?->total_completed_jobs ?? 0,
                'rating' => $this->workerProfile?->avg_rating ?? 0,
            ],

            // Ensure status strings are always returned (not null)
            'profile_status' => $this->workerProfile?->profile_status ?? WorkerProfileStatus::INCOMPLETE,
            'activity_status' => $this->workerProfile?->activity_status ?? WorkerActivityStatus::INACTIVE,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

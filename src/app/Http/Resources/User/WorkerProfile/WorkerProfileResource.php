<?php

namespace App\Http\Resources\User\WorkerProfile;

use App\Http\Resources\Common\ImageSimpleResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar_code' => $this->avatar_code,
            'avatar' => $this->avatar ? new ImageSimpleResource($this->avatar) : null,
            'gender' => $this->gender,
            'date_of_birth' => $this->dob ? $this->dob->format('Y-m-d') : null,
            'experience_years' => $this->experience_years,
            'skill_description' => $this->skill_description,
            'certificates' => $this->certificates ?? [],
            'profile_status' => $this->profile_status,
            'activity_status' => $this->activity_status,
            'availability' => $this->availability,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at,
            'services' => $this->whenLoaded('services', function () {
                return WorkerProfileServiceResource::collection($this->services->filter(fn ($item) => $item->serviceCategory));
            }),
            'areas' => $this->whenLoaded('areas', function () {
                return WorkerProfileAreaResource::collection($this->areas->filter(fn ($item) => $item->area));
            }),
        ];
    }
}

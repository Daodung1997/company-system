<?php

namespace App\Http\Resources\User\WorkerProfile;

use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicWorkerProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'avatar_code' => $this->avatar_code,
            'avatar' => $this->avatar ? new ImageSimpleResource($this->avatar) : null,
            'experience_years' => $this->experience_years,
            'skill_description' => $this->skill_description,
            'avg_rating' => $this->avg_rating,
            'total_completed_jobs' => $this->total_completed_jobs ?? 0,
            'services' => $this->whenLoaded('services', function () {
                return WorkerProfileServiceResource::collection($this->services->filter(fn ($item) => $item->serviceCategory));
            }),
            'areas' => $this->whenLoaded('areas', function () {
                return WorkerProfileAreaResource::collection($this->areas->filter(fn ($item) => $item->area));
            }),
        ];
    }
}

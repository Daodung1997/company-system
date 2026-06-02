<?php

namespace App\Http\Resources\User\Job;

use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobWorkerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_code' => $this->workerProfile?->avatar_code,
            'avatar' => $this->workerProfile?->avatar ? new ImageSimpleResource($this->workerProfile->avatar) : null,
            'avg_rating' => $this->workerProfile?->avg_rating,
        ];
    }
}

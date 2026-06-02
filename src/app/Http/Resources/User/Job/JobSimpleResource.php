<?php

namespace App\Http\Resources\User\Job;

use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobSimpleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'service' => $this->relationLoaded('serviceCategory') && $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
        ];
    }
}

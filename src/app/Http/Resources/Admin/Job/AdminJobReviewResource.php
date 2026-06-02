<?php

namespace App\Http\Resources\Admin\Job;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reviewer' => $this->relationLoaded('reviewer') && $this->reviewer ? new UserSimpleResource($this->reviewer) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

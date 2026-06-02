<?php

namespace App\Http\Resources\User\Review;

use App\Http\Resources\User\Job\JobSimpleResource;
use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->relationLoaded('reviewer') && $this->reviewer ? new UserSimpleResource($this->reviewer) : null,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'job' => $this->relationLoaded('job') && $this->job ? new JobSimpleResource($this->job) : null,
            'created_at' => $this->created_at,
        ];
    }
}

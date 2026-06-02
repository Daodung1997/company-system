<?php

namespace App\Http\Resources\Admin\Job;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobQuotationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'worker' => $this->relationLoaded('worker') && $this->worker ? new UserSimpleResource($this->worker) : null,
            'price' => (float) $this->price,
            'estimated_duration' => $this->estimated_duration,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

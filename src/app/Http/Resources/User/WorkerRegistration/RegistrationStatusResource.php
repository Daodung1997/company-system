<?php

namespace App\Http\Resources\User\WorkerRegistration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'profile_status' => $this->resource['profile_status'] ?? 'not_started',
            'rejection_reason' => $this->resource['rejection_reason'] ?? null,
            'approved_at' => $this->resource['approved_at'] ?? null,
            'submitted_at' => $this->resource['submitted_at'] ?? null,
        ];
    }
}

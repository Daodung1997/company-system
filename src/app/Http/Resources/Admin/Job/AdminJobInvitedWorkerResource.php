<?php

namespace App\Http\Resources\Admin\Job;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobInvitedWorkerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->pivot->status,
            'invited_at' => $this->pivot->created_at?->toIso8601String(),
        ];
    }
}

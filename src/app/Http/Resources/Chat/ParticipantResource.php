<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->user->name ?? null,
            'avatar' => $this->user->avatar ?? null,
            'is_read' => (bool) $this->is_read,
            'last_read_at' => $this->last_read_at ? $this->last_read_at->toIso8601String() : null,
        ];
    }
}

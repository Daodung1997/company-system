<?php

namespace App\Http\Resources\Chat;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => $this->relationLoaded('sender') && $this->sender ? new UserSimpleResource($this->sender) : null,
            'content' => $this->content,
            'type' => $this->type,
            'read_at' => $this->read_at ? $this->read_at->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

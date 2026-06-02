<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Determine unread status for current user if available
        $currentUserParticipant = $this->participants->where('user_id', auth()->id())->first();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'related_id' => $this->related_id,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at ? $this->last_message_at->toIso8601String() : null,
            'last_message_content' => $this->last_message_content,
            'participants' => ParticipantResource::collection($this->participants),
            // 'messages' => MessageResource::collection($this->whenLoaded('messages')), // Usually loaded separately
            'unread_count' => $currentUserParticipant && ! $currentUserParticipant->is_read ? 1 : 0, // Simplified
            'is_read' => $currentUserParticipant ? (bool) $currentUserParticipant->is_read : true,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

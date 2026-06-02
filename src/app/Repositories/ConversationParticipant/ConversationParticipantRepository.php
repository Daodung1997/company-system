<?php

namespace App\Repositories\ConversationParticipant;

use App\Models\ConversationParticipant;
use App\Repositories\Repository;

class ConversationParticipantRepository extends Repository
{
    public function model(): string
    {
        return ConversationParticipant::class;
    }

    public function __construct(ConversationParticipant $model)
    {
        parent::__construct($model);
    }

    /**
     * Check if a user is a participant of a conversation.
     */
    public function isParticipant(int $conversationId, int $userId): bool
    {
        return $this->model
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Mark conversation as read for a specific user.
     */
    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->model
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update([
                'is_read' => true,
                'last_read_at' => now(),
            ]);
    }

    /**
     * Mark conversation as unread for all users except the sender.
     */
    public function markOthersUnread(int $conversationId, int $excludeUserId): void
    {
        $this->model
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $excludeUserId)
            ->update(['is_read' => false]);
    }

    /**
     * Add a participant to a conversation.
     */
    public function addParticipant(int $conversationId, int $userId): ConversationParticipant
    {
        return $this->create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'is_read' => true,
            'last_read_at' => now(),
            'is_muted' => false,
        ]);
    }
}

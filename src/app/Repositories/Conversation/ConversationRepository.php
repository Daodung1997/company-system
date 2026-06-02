<?php

namespace App\Repositories\Conversation;

use App\Models\Conversation;
use App\Repositories\Repository;

class ConversationRepository extends Repository
{
    public function model(): string
    {
        return Conversation::class;
    }

    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    public function filterByUserId($userId)
    {
        $this->model = $this->model->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });

        return $this;
    }

    /**
     * Find an existing conversation between the exact set of participants.
     * Optionally filter by type and relatedId (e.g. JOB type with job_id).
     */
    public function findByParticipants(array $userIds, ?string $type = null, ?int $relatedId = null): ?Conversation
    {
        $query = Conversation::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($relatedId) {
            $query->where('related_id', $relatedId);
        }

        // Find conversations that contain ALL the specified user IDs as participants
        foreach ($userIds as $userId) {
            $query->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        return $query->first();
    }

    /**
     * Check if a user is a participant of a conversation.
     */
    public function isParticipant(int $conversationId, int $userId): bool
    {
        return Conversation::where('id', $conversationId)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }
}

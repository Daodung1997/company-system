<?php

namespace App\Services\Chat;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Conversation\ConversationStatusConst;
use App\Constants\Master\Models\Conversation\ConversationTypeConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Message\MessageTypeConst;
use App\Exceptions\BusinessException;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\ConversationParticipant\ConversationParticipantRepository;
use App\Repositories\Message\MessageRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Log;

class ChatService extends AbstractService
{
    protected $conversationRepository;

    protected $messageRepository;

    protected $participantRepository;

    public function __construct(
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        ConversationParticipantRepository $participantRepository
    ) {
        $this->conversationRepository = $conversationRepository;
        $this->messageRepository = $messageRepository;
        $this->participantRepository = $participantRepository;
    }

    /**
     * Validate that a user is a participant of a conversation.
     * Throws BusinessException if not.
     */
    public function validateParticipant(int $conversationId, int $userId): void
    {
        if (! $this->participantRepository->isParticipant($conversationId, $userId)) {
            throw new BusinessException(
                ExceptionCode::CHAT_NOT_PARTICIPANT,
                'You are not a participant of this conversation',
                403
            );
        }
    }

    /**
     * Start a new conversation or return existing one (get-or-create).
     */
    public function startConversation(array $userIds, string $type = ConversationTypeConst::DIRECT, ?int $relatedId = null): Conversation
    {
        $currentUser = auth()->id();

        // Ensure creator is in the participants list
        if (! in_array($currentUser, $userIds)) {
            $userIds[] = $currentUser;
        }

        // Check if conversation already exists between these users
        $existing = $this->conversationRepository->findByParticipants($userIds, $type, $relatedId);
        if ($existing) {
            return $existing->load('participants');
        }

        $this->beginTransaction();
        try {
            $conversation = $this->conversationRepository->create([
                'type' => $type,
                'related_id' => $relatedId,
                'creator_id' => $currentUser,
                'status' => ConversationStatusConst::ACTIVE,
                'last_message_at' => now(),
            ]);

            foreach ($userIds as $userId) {
                $this->participantRepository->addParticipant($conversation->id, $userId);
            }

            $this->commitTransaction();

            return $conversation->load('participants');
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Send a message to a conversation.
     * Validates participant before sending.
     */
    public function sendMessage(int $conversationId, int $senderId, string $content, string $type = MessageTypeConst::TEXT): Message
    {
        $this->validateParticipant($conversationId, $senderId);

        $conversation = $this->conversationRepository->find($conversationId);
        if ($conversation && $conversation->type === ConversationTypeConst::JOB && $conversation->related_id) {
            $job = \App\Models\Job::find($conversation->related_id);
            if ($job && in_array($job->status, JobStatusConst::completedStatuses())) {
                throw new BusinessException(
                    ExceptionCode::INVALID_STATUS,
                    'Cannot send message because the job is no longer active',
                    400
                );
            }
        }

        $this->beginTransaction();
        try {
            $message = $this->messageRepository->create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'content' => $content,
                'type' => $type,
                'read_at' => null,
            ]);

            // Update conversation last message info
            $this->conversationRepository->update($conversationId, [
                'last_message_at' => now(),
                'last_message_content' => $type === MessageTypeConst::TEXT ? $content : '[Attachment]',
            ]);

            // Mark sender as read, others as unread
            $this->participantRepository->markOthersUnread($conversationId, $senderId);
            $this->participantRepository->markAsRead($conversationId, $senderId);

            $this->commitTransaction();

            // Broadcast event (non-blocking)
            try {
                event(new \App\Events\Chat\MessageSent($message->load('sender')));
            } catch (\Exception $e) {
                Log::error('Broadcast error: '.$e->getMessage());
            }

            return $message;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Mark a conversation as read for a user.
     * Validates participant before marking.
     */
    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->validateParticipant($conversationId, $userId);
        $this->participantRepository->markAsRead($conversationId, $userId);
    }

    /**
     * Upload a chat media file.
     */
    public function uploadImage($file): string
    {
        $path = $file->store('chat-media', 'public');

        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }
}

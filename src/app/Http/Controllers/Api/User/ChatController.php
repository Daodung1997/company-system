<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Requests\Chat\UploadMessageMediaRequest;
use App\Http\Resources\Chat\ConversationResource;
use App\Http\Resources\Chat\MessageResource;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\Criteria\Conversation\SortAndFilterConversationCriteria;
use App\Repositories\Criteria\Message\SortAndFilterMessageCriteria;
use App\Repositories\Message\MessageRepository;
use App\Services\Chat\ChatService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatService;

    protected $conversationRepository;

    protected $messageRepository;

    public function __construct(
        ChatService $chatService,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository
    ) {
        $this->chatService = $chatService;
        $this->conversationRepository = $conversationRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * List conversations for current user.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 15);
        $filters = $request->query('filters', []);
        $sorts = $request->query('sorts', []);
        $search = $request->query('search', []);

        $userId = auth()->id();
        $this->conversationRepository->filterByUserId($userId);

        $conversations = $this->conversationRepository->pushCriteria(
            new SortAndFilterConversationCriteria($filters, $sorts, $search)
        )->paginate($limit);

        return Response::pagination(
            ConversationResource::collection($conversations),
            $conversations->total(),
            $conversations->currentPage(),
            $conversations->perPage()
        );
    }

    /**
     * Start a new conversation or return existing one.
     */
    public function start(StartConversationRequest $request)
    {
        $conversation = $this->chatService->startConversation(
            $request->user_ids,
            $request->input('type', 'DIRECT'),
            $request->related_id
        );

        return Response::success((new ConversationResource($conversation))->resolve());
    }

    /**
     * Get messages of a conversation.
     * IDOR: validateParticipant is called in service layer.
     */
    public function show(Request $request, int $id)
    {
        // Validate participant access
        $this->chatService->validateParticipant($id, auth()->id());

        $limit = $request->query('limit', 50);
        $filters = $request->query('filters', []);
        $filters['conversation_id'] = $id;

        $messages = $this->messageRepository->pushCriteria(
            new SortAndFilterMessageCriteria($filters, [], [])
        )->paginate($limit);

        return Response::pagination(
            MessageResource::collection($messages),
            $messages->total(),
            $messages->currentPage(),
            $messages->perPage()
        );
    }

    /**
     * Send a message to a conversation.
     * IDOR: validateParticipant is called inside sendMessage().
     */
    public function store(StoreMessageRequest $request, int $id)
    {
        $message = $this->chatService->sendMessage(
            $id,
            auth()->id(),
            $request->input('content'),
            $request->input('type', 'TEXT')
        );

        return Response::success((new MessageResource($message))->resolve());
    }

    /**
     * Mark conversation messages as read.
     * IDOR: validateParticipant is called inside markAsRead().
     */
    public function update(Request $request, int $id)
    {
        $this->chatService->markAsRead($id, auth()->id());

        return Response::success([]);
    }

    /**
     * Upload chat media file.
     */
    public function upload(UploadMessageMediaRequest $request)
    {
        $url = $this->chatService->uploadImage($request->file('file'));

        return Response::success(['url' => $url]);
    }
}

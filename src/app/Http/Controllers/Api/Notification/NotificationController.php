<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * GET /api/notifications
     * List notifications for the current user (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $params = [
            'limit' => $request->get('limit', 20),
            'unread_only' => $request->boolean('unread_only', false),
            'keyword' => $request->get('keyword'),
            'type' => $request->get('type'),
        ];

        $notifications = $this->notificationService->getForUser($userId, $params);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/notifications/unread-count
     * Get count of unread notifications.
     */
    public function unreadCount(): JsonResponse
    {
        $userId = auth()->id();
        $count = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'data' => ['count' => $count],
        ]);
    }

    /**
     * PUT /api/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $userId = auth()->id();
        $this->notificationService->markAsRead($id, $userId);

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * PUT /api/notifications/read-all
     * Mark all notifications as read for the current user.
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = auth()->id();
        $count = $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'message' => "{$count} notifications marked as read.",
            'data' => ['count' => $count],
        ]);
    }
}

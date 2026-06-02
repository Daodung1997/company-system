<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Notification\DeleteNotificationsRequest;
use App\Http\Requests\User\Notification\RegisterDeviceTokenRequest;
use App\Http\Resources\User\Notification\NotificationResource;
use App\Repositories\Criteria\Notification\SortAndFilterNotificationCriteria;
use App\Repositories\Notification\NotificationRepository;
use App\Services\User\NotificationService;
use App\Supports\Facades\Response\Response;

class NotificationController extends Controller
{
    protected $notificationRepository;

    protected $notificationService;

    public function __construct(
        NotificationRepository $notificationRepository,
        NotificationService $notificationService
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->notificationService = $notificationService;
    }

    public function index(\App\Http\Requests\User\Notification\ListNotificationsRequest $request)
    {
        $userId = auth()->id();
        $filters = $request->query('filters', []);
        $filters['user_id'] = $userId;
        $sorts = $request->query('sorts', []);
        $search = $request->query('search', []);
        $limit = $request->query('limit', 15);

        $notifications = $this->notificationRepository->pushCriteria(
            new SortAndFilterNotificationCriteria($filters, $sorts, $search)
        )->paginate($limit);

        $unreadCount = $this->notificationRepository->getUnreadCount($userId);

        return Response::pagination(
            NotificationResource::collection($notifications),
            $notifications->total(),
            $notifications->currentPage(),
            $notifications->perPage(),
            ['unread_count' => $unreadCount]
        );
    }

    public function read($id)
    {
        $userId = auth()->id();
        $this->notificationService->markAsRead($id, $userId);

        return Response::success([], 'Notification marked as read');
    }

    public function readAll()
    {
        $userId = auth()->id();
        $this->notificationService->markAllAsRead($userId);

        return Response::success([], 'All notifications marked as read');
    }

    public function registerDeviceToken(RegisterDeviceTokenRequest $request)
    {
        $userId = auth()->id();
        $this->notificationService->registerDeviceToken($userId, $request->validated());

        return Response::success(['message' => 'Device token registered successfully']);
    }

    public function destroy(DeleteNotificationsRequest $request)
    {
        $userId = auth()->id();
        $ids = $request->input('ids');

        $this->notificationService->deleteNotifications($userId, $ids);

        return Response::success([], 'Notifications deleted successfully');
    }
}

<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Services\AbstractService;

class NotificationService extends AbstractService
{
    /**
     * Create a notification for a specific user.
     */
    public static function send(int $userId, string $type, string $title, string $body, ?string $actionUrl = null, ?array $extraData = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => array_merge($extraData ?? [], [
                'action_url' => $actionUrl,
            ]),
        ]);
    }

    /**
     * Send notification to all users with a specific role.
     */
    public static function sendToRole(string $role, string $type, string $title, string $body, ?string $actionUrl = null, ?array $extraData = []): void
    {
        $employees = \App\Models\Employee::where('role', $role)->get();
        foreach ($employees as $employee) {
            self::send($employee->id, $type, $title, $body, $actionUrl, $extraData);
        }
    }

    /**
     * Get notifications for a user (paginated).
     */
    public function getForUser(int $userId, array $params = [])
    {
        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (isset($params['unread_only']) && $params['unread_only']) {
            $query->whereNull('read_at');
        }

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                  ->orWhere('body', 'LIKE', "%{$keyword}%");
            });
        }

        $limit = $params['limit'] ?? 20;
        return $query->paginate($limit);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]) > 0;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

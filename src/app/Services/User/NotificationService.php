<?php

namespace App\Services\User;

use App\Repositories\Notification\NotificationRepository;
use App\Repositories\User\UserDeviceRepository;
use App\Repositories\User\UserRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Log;

class NotificationService extends AbstractService
{
    protected $notificationRepository;

    protected $userDeviceRepository;

    protected $userRepository;

    public function __construct(
        NotificationRepository $notificationRepository,
        UserDeviceRepository $userDeviceRepository,
        UserRepository $userRepository
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->userDeviceRepository = $userDeviceRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Create notification and send push
     */
    public function sendNotification(int $userId, string $type, string $title, string $body, array $data = [])
    {
        $this->beginTransaction();
        try {
            // 1. Store in DB
            $notification = $this->notificationRepository->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'created_by' => 'system',
            ]);

            $this->commitTransaction();

            // 2. Get User Devices (Tokens)
            $tokens = $this->userDeviceRepository->getTokensByUserId($userId);

            // 3. Send Push via FCM in background
            if (! empty($tokens)) {
                \App\Jobs\SendPushNotificationJob::dispatch(
                    $tokens,
                    $title,
                    $body,
                    array_merge($data, [
                        'notification_id' => $notification->id,
                        'type' => $type,
                    ])
                );
            }

            return $notification;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('Notification Error: '.$e->getMessage());

            // Don't block main flow if notification fails
            return null;
        }
    }

    public function markAsRead($id, $userId)
    {
        $this->beginTransaction();
        try {
            $result = $this->notificationRepository->updateWhere(
                ['id' => $id, 'user_id' => $userId],
                ['read_at' => now()]
            );
            $this->commitTransaction();

            return $result;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function markAllAsRead($userId)
    {
        $this->beginTransaction();
        try {
            $result = $this->notificationRepository->updateWhere(
                ['user_id' => $userId, 'read_at' => null],
                ['read_at' => now()]
            );
            $this->commitTransaction();

            return $result;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function registerDeviceToken(int $userId, array $data)
    {
        $this->beginTransaction();
        try {
            $device = $this->userDeviceRepository->registerDevice($userId, $data);
            $this->commitTransaction();

            return $device;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Broadcast notification to all active workers (open job fallback).
     */
    public function broadcastToWorkers(string $type, string $title, string $body, array $data = [])
    {
        try {
            $workerIds = $this->userRepository->getActiveWorkerIds();

            foreach ($workerIds as $userId) {
                $this->sendNotification($userId, $type, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            Log::error('Broadcast Notification Error: '.$e->getMessage());
        }
    }

    /**
     * Delete notifications selectively or entirely
     */
    public function deleteNotifications(int $userId, ?array $ids = null)
    {
        $this->beginTransaction();
        try {
            if (! empty($ids)) {
                $status = $this->notificationRepository->deleteByIds($ids, $userId);
            } else {
                $status = $this->notificationRepository->deleteAllByUserId($userId);
            }
            $this->commitTransaction();

            return $status;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}

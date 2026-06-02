<?php

namespace App\Repositories\User;

use App\Models\UserDevice;
use App\Repositories\Repository;

class UserDeviceRepository extends Repository
{
    public function __construct(UserDevice $model)
    {
        $this->model = $model;
    }

    public function getTokensByUserId($userId)
    {
        return $this->model->where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();
    }

    /**
     * Update or Create user device
     *
     * @return mixed
     */
    public function registerDevice(int $userId, array $data)
    {
        // One device_id per user
        return $this->model->updateOrCreate(
            [
                'user_id' => $userId,
                'device_id' => $data['device_id'],
            ],
            [
                'fcm_token' => $data['fcm_token'],
                'device_name' => $data['device_name'] ?? 'Unknown',
                'device_type' => $data['device_type'] ?? null,
                'last_active_at' => now(),
            ]
        );
    }
}

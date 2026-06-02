<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use App\Repositories\Repository;

class NotificationRepository extends Repository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    public function updateWhere(array $where, array $data)
    {
        $this->applyCriteria();
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
        $status = $this->model->update($data);
        $this->resetModel();

        return $status;
    }

    public function getUnreadCount(int $userId): int
    {
        $count = $this->model->where('user_id', $userId)->whereNull('read_at')->count();
        $this->resetModel();

        return $count;
    }

    public function deleteByIds(array $ids, int $userId)
    {
        $status = $this->model->whereIn('id', $ids)->where('user_id', $userId)->delete();
        $this->resetModel();

        return $status;
    }

    public function deleteAllByUserId(int $userId)
    {
        $status = $this->model->where('user_id', $userId)->delete();
        $this->resetModel();

        return $status;
    }
}

<?php

namespace App\Repositories\User;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\User;
use App\Repositories\Repository;

class UserRepository extends Repository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all active worker user IDs for broadcast notifications.
     */
    public function getActiveWorkerIds(): array
    {
        return $this->model
            ->where('role', UserRoleConst::WORKER)
            ->where('status', CommonStatusConst::ACTIVE)
            ->pluck('id')
            ->toArray();
    }
}

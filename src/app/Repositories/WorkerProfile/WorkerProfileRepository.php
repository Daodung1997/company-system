<?php

namespace App\Repositories\WorkerProfile;

use App\Models\WorkerProfile;
use App\Repositories\Repository;

class WorkerProfileRepository extends Repository
{
    public function __construct(WorkerProfile $model)
    {
        parent::__construct($model);
    }
}

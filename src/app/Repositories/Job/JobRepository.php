<?php

namespace App\Repositories\Job;

use App\Models\Job;
use App\Repositories\Repository;

class JobRepository extends Repository implements JobRepositoryInterface
{
    public function __construct(Job $model)
    {
        parent::__construct($model);
    }
}

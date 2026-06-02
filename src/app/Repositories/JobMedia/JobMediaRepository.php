<?php

namespace App\Repositories\JobMedia;

use App\Models\JobMedia;
use App\Repositories\Repository;

class JobMediaRepository extends Repository
{
    public function __construct(JobMedia $model)
    {
        parent::__construct($model);
    }
}

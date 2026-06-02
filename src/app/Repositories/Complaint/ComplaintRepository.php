<?php

namespace App\Repositories\Complaint;

use App\Models\Complaint;
use App\Repositories\Repository;

class ComplaintRepository extends Repository
{
    public function __construct(Complaint $model)
    {
        parent::__construct($model);
    }
}

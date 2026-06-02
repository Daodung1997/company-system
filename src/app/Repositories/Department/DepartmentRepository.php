<?php

namespace App\Repositories\Department;

use App\Models\Department;
use App\Repositories\Repository;

class DepartmentRepository extends Repository
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }
}

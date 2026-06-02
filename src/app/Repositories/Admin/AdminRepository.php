<?php

namespace App\Repositories\Admin;

use App\Models\Admin;
use App\Repositories\Repository;

class AdminRepository extends Repository
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }
}

<?php

namespace App\Repositories\Admin;

use App\Repositories\Repository;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends Repository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }
}

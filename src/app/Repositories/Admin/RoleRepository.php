<?php

namespace App\Repositories\Admin;

use App\Repositories\Repository;
use Spatie\Permission\Models\Role;

class RoleRepository extends Repository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }
}

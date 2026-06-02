<?php

namespace App\Repositories\AdminToken;

use App\Models\AdminToken;
use App\Repositories\Repository;

class AdminTokenRepository extends Repository
{
    public function __construct(AdminToken $model)
    {
        parent::__construct($model);
    }
}

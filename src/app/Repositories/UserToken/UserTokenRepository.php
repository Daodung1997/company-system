<?php

namespace App\Repositories\UserToken;

use App\Models\UserToken;
use App\Repositories\Repository;

class UserTokenRepository extends Repository
{
    public function __construct(UserToken $model)
    {
        parent::__construct($model);
    }
}

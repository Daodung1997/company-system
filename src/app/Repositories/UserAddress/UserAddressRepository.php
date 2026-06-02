<?php

namespace App\Repositories\UserAddress;

use App\Models\UserAddress;
use App\Repositories\Repository;

class UserAddressRepository extends Repository
{
    public function __construct(UserAddress $model)
    {
        parent::__construct($model);
    }
}

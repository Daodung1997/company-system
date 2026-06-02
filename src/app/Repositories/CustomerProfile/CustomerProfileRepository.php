<?php

namespace App\Repositories\CustomerProfile;

use App\Models\CustomerProfile;
use App\Repositories\Repository;

class CustomerProfileRepository extends Repository
{
    public function __construct(CustomerProfile $model)
    {
        parent::__construct($model);
    }
}

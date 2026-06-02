<?php

namespace App\Repositories\Discount;

use App\Models\Discount;
use App\Repositories\Repository;

class DiscountRepository extends Repository
{
    public function __construct(Discount $model)
    {
        parent::__construct($model);
    }
}

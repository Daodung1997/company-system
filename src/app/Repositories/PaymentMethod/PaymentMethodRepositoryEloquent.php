<?php

namespace App\Repositories\PaymentMethod;

use App\Models\PaymentMethod;
use App\Repositories\Repository;

class PaymentMethodRepositoryEloquent extends Repository implements PaymentMethodRepository
{
    public function __construct(PaymentMethod $model)
    {
        parent::__construct($model);
    }
}

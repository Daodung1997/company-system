<?php

namespace App\Repositories\Wallet;

use App\Models\WithdrawalLog;
use App\Repositories\Repository;

class WithdrawalLogRepository extends Repository
{
    public function __construct(WithdrawalLog $model)
    {
        parent::__construct($model);
    }
}

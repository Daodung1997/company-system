<?php

namespace App\Constants\Transaction\Models\Transaction;

use App\Traits\ConstTrait;

class TransactionTypeConst
{
    use ConstTrait;

    const EXPENSE = 'EXPENSE';
    const REVENUE = 'REVENUE';

    const LABELS = [
        self::EXPENSE => 'Chi phí',
        self::REVENUE => 'Doanh thu',
    ];
}

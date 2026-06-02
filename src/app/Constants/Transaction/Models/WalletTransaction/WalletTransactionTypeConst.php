<?php

namespace App\Constants\Transaction\Models\WalletTransaction;

use App\Traits\ConstTrait;

class WalletTransactionTypeConst
{
    use ConstTrait;

    const EARNING = 'earning';

    const WITHDRAWAL = 'withdrawal';

    const FEE = 'fee';
}

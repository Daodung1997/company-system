<?php

namespace App\Constants\Transaction\Models\WalletTransaction;

use App\Traits\ConstTrait;

class WalletTransactionStatusConst
{
    use ConstTrait;

    const PENDING = 'pending';

    const RELEASED = 'released';

    const COMPLETED = 'completed';

    const FAILED = 'failed';
}

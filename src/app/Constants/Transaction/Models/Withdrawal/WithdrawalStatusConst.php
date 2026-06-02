<?php

namespace App\Constants\Transaction\Models\Withdrawal;

use App\Traits\ConstTrait;

class WithdrawalStatusConst
{
    use ConstTrait;

    const REQUESTED = 'requested';

    const PROCESSING = 'processing';

    const COMPLETED = 'completed';

    const FAILED = 'failed';
}

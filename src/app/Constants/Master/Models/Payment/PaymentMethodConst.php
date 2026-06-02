<?php

namespace App\Constants\Master\Models\Payment;

use App\Traits\ConstTrait;

class PaymentMethodConst
{
    use ConstTrait;

    const CASH = 'cash';

    const BANK_TRANSFER = 'bank_transfer';

    const VNPAY = 'VNPAY'; // Reserved for future
}

<?php

namespace App\Constants\Transaction\Models\Transaction;

use App\Traits\ConstTrait;

class PaymentMethodConst
{
    use ConstTrait;

    const BANK_TRANSFER = 'BANK_TRANSFER';
    const CASH = 'CASH';
    const CREDIT_CARD = 'CREDIT_CARD';

    const LABELS = [
        self::BANK_TRANSFER => 'Chuyển khoản ngân hàng',
        self::CASH => 'Tiền mặt',
        self::CREDIT_CARD => 'Thẻ tín dụng',
    ];
}

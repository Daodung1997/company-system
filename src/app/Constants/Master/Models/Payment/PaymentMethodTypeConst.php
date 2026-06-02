<?php

namespace App\Constants\Master\Models\Payment;

use App\Traits\ConstTrait;

class PaymentMethodTypeConst
{
    use ConstTrait;

    public const CASH = 'cash';

    public const BANK_TRANSFER = 'bank_transfer';

    public const TRANSFER = 'bank_transfer'; // Alias for task requirement

    public const VNPAY = 'VNPAY';

    public const EWALLET = 'ewallet';

    public static function getValues(): array
    {
        return [
            self::CASH,
            self::BANK_TRANSFER,
            self::TRANSFER,
            self::VNPAY,
            self::EWALLET,
        ];
    }
}

<?php

namespace App\Constants\Master\Models\Payment;

class PaymentRelation
{
    public const JOB = 'job';

    public const PAYMENT_METHOD_DETAIL = 'paymentMethodDetail';

    public static function getValues(): array
    {
        return [
            self::JOB,
            self::PAYMENT_METHOD_DETAIL,
        ];
    }
}

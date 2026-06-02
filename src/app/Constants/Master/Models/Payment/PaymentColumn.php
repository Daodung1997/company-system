<?php

namespace App\Constants\Master\Models\Payment;

class PaymentColumn
{
    public const ID = 'id';

    public const CODE = 'code';

    public const JOB_ID = 'job_id';

    public const AMOUNT = 'amount';

    public const PLATFORM_FEE = 'platform_fee';

    public const WORKER_EARNING = 'worker_earning';

    public const PAYMENT_METHOD = 'payment_method';

    public const GATEWAY_PROVIDER = 'gateway_provider';

    public const GATEWAY_ORDER_ID = 'gateway_order_id';

    public const GATEWAY_REQUEST_DATA = 'gateway_request_data';

    public const STATUS = 'status';

    public const TRANSACTION_REFERENCE = 'transaction_reference';

    public const PAID_AT = 'paid_at';

    public const REFUNDED_AT = 'refunded_at';

    public const REFUNDED_AMOUNT = 'refunded_amount';

    public const DESCRIPTION = 'description';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const CREATED_BY = 'created_by';

    public const UPDATED_BY = 'updated_by';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::CODE,
            self::JOB_ID,
            self::AMOUNT,
            self::PLATFORM_FEE,
            self::WORKER_EARNING,
            self::PAYMENT_METHOD,
            self::GATEWAY_PROVIDER,
            self::GATEWAY_ORDER_ID,
            self::GATEWAY_REQUEST_DATA,
            self::STATUS,
            self::TRANSACTION_REFERENCE,
            self::PAID_AT,
            self::REFUNDED_AT,
            self::REFUNDED_AMOUNT,
            self::DESCRIPTION,
            self::CREATED_AT,
            self::UPDATED_AT,
            self::CREATED_BY,
            self::UPDATED_BY,
        ];
    }
}

<?php

namespace App\Constants\Master\Models\PaymentMethod;

class PaymentMethodColumn
{
    public const ID = 'id';

    public const CODE = 'code';

    public const NAME = 'name';

    public const TYPE = 'type';

    public const CONFIG = 'config';

    public const ICON_URL = 'icon_url';

    public const STATUS = 'status';

    public const SORT_ORDER = 'sort_order';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const DELETED_AT = 'deleted_at';

    public const CREATED_BY = 'created_by';

    public const UPDATED_BY = 'updated_by';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::CODE,
            self::NAME,
            self::TYPE,
            self::CONFIG,
            self::ICON_URL,
            self::STATUS,
            self::SORT_ORDER,
            self::CREATED_AT,
            self::UPDATED_AT,
            self::DELETED_AT,
            self::CREATED_BY,
            self::UPDATED_BY,
        ];
    }
}

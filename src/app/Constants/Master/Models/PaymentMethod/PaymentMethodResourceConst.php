<?php

namespace App\Constants\Master\Models\PaymentMethod;

class PaymentMethodResourceConst
{
    public const ID = 'id';

    public const CODE = 'code';

    public const NAME = 'name';

    public const TYPE = 'type';

    public const CONFIG = 'config';

    public const ICON_URL = 'icon_url';

    public const STATUS = 'status';

    public const SORT_ORDER = 'sort_order';

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
        ];
    }
}

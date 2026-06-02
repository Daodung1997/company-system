<?php

namespace App\Constants\Master\Models\User;

class UserRoleConst
{
    public const WORKER = 'worker';

    public const CUSTOMER = 'customer';

    public const ADMIN = 'admin';

    public static function All()
    {
        return [
            self::WORKER,
            self::CUSTOMER,
            self::ADMIN,
        ];
    }
}

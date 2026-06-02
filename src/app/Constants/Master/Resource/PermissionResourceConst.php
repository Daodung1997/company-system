<?php

namespace App\Constants\Master\Resource;

class PermissionResourceConst
{
    public const ID = 'id';

    public const NAME = 'name';

    public const GUARD_NAME = 'guard_name';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::NAME,
            self::GUARD_NAME,
        ];
    }
}

<?php

namespace App\Constants\Master\Resource;

class RoleResourceConst
{
    public const ID = 'id';

    public const NAME = 'name';

    public const GUARD_NAME = 'guard_name';

    public const PERMISSIONS = 'permissions';

    public const USERS_COUNT = 'users_count';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::NAME,
            self::GUARD_NAME,
            self::PERMISSIONS,
            self::USERS_COUNT,
            self::CREATED_AT,
            self::UPDATED_AT,
        ];
    }
}

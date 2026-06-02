<?php

namespace App\Constants\Master\Models\Permission;

class PermissionColumn
{
    public const ID = 'id';

    public const NAME = 'name';

    public const GUARD_NAME = 'guard_name';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::NAME,
            self::GUARD_NAME,
            self::CREATED_AT,
            self::UPDATED_AT,
        ];
    }
}

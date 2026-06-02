<?php

namespace App\Constants\Master\Models\Role;

class RoleRelation
{
    public const PERMISSIONS = 'permissions';

    public const USERS = 'users';

    public static function getValues(): array
    {
        return [
            self::PERMISSIONS,
            self::USERS,
        ];
    }
}

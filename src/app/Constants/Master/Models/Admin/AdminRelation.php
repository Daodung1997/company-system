<?php

namespace App\Constants\Master\Models\Admin;

class AdminRelation
{
    public const ROLES = 'roles';

    public const PERMISSIONS = 'permissions';

    public static function getValues(): array
    {
        return [
            self::ROLES,
            self::PERMISSIONS,
        ];
    }
}

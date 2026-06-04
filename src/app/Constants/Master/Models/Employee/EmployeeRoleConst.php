<?php

namespace App\Constants\Master\Models\Employee;

class EmployeeRoleConst
{
    public const ADMIN = 'ADMIN';

    public const MANAGER = 'MANAGER';

    public const HR = 'HR';

    public const ACCOUNTANT = 'ACCOUNTANT';

    public const STAFF = 'STAFF';

    public static function getValues(): array
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::HR,
            self::ACCOUNTANT,
            self::STAFF,
        ];
    }
}

<?php

namespace App\Constants\Master\Models\Employee;

class EmployeeRelation
{
    public const COMPANY = 'company';

    public const DEPARTMENT = 'department';

    public const RELATIVES = 'relatives';

    public static function getValues(): array
    {
        return [
            self::COMPANY,
            self::DEPARTMENT,
            self::RELATIVES,
        ];
    }
}

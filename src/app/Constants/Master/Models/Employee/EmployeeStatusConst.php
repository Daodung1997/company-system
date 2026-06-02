<?php

namespace App\Constants\Master\Models\Employee;

class EmployeeStatusConst
{
    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    public const PROBATION = 'PROBATION';

    public static function getValues(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::PROBATION,
        ];
    }
}

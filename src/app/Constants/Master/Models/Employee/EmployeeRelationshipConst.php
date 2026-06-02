<?php

namespace App\Constants\Master\Models\Employee;

class EmployeeRelationshipConst
{
    public const SPOUSE = 'SPOUSE';

    public const CHILD = 'CHILD';

    public const PARENT = 'PARENT';

    public const SIBLING = 'SIBLING';

    public const OTHER = 'OTHER';

    public static function getValues(): array
    {
        return [
            self::SPOUSE,
            self::CHILD,
            self::PARENT,
            self::SIBLING,
            self::OTHER,
        ];
    }
}

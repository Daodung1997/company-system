<?php

namespace App\Constants\Master\Models\Employee;

class EmployeeColumn
{
    public const ID = 'id';

    public const CODE = 'code';

    public const DEPARTMENT_ID = 'department_id';

    public const FULL_NAME = 'full_name';

    public const FULL_NAME_KANA = 'full_name_kana';

    public const ROMAJI_NAME = 'romaji_name';

    public const EMAIL = 'email';

    public const PHONE = 'phone';

    public const ROLE = 'role';

    public const STATUS = 'status';

    public const JOIN_DATE = 'join_date';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::CODE,
            self::DEPARTMENT_ID,
            self::FULL_NAME,
            self::FULL_NAME_KANA,
            self::ROMAJI_NAME,
            self::EMAIL,
            self::PHONE,
            self::ROLE,
            self::STATUS,
            self::JOIN_DATE,
            self::CREATED_AT,
            self::UPDATED_AT,
        ];
    }
}

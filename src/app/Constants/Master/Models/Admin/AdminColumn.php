<?php

namespace App\Constants\Master\Models\Admin;

class AdminColumn
{
    public const ID = 'id';

    public const CODE = 'code';

    public const NAME = 'name';

    public const EMAIL = 'email';

    public const PASSWORD = 'password';

    public const STATUS = 'status';

    public const AVATAR_URL = 'avatar_url';

    public const LAST_LOGIN_AT = 'last_login_at';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const CREATED_BY = 'created_by';

    public const UPDATED_BY = 'updated_by';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::CODE,
            self::NAME,
            self::EMAIL,
            self::PASSWORD,
            self::STATUS,
            self::AVATAR_URL,
            self::LAST_LOGIN_AT,
            self::CREATED_AT,
            self::UPDATED_AT,
            self::CREATED_BY,
            self::UPDATED_BY,
        ];
    }
}

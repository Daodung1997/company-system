<?php

namespace App\Constants\Master\Resource;

class AdminResourceConst
{
    public const ID = 'id';

    public const CODE = 'code';

    public const NAME = 'name';

    public const EMAIL = 'email';

    public const STATUS = 'status';

    public const AVATAR_URL = 'avatar_url';

    public const LAST_LOGIN_AT = 'last_login_at';

    public const ROLES = 'roles';

    public const PERMISSIONS = 'permissions';

    public static function getValues(): array
    {
        return [
            self::ID,
            self::CODE,
            self::NAME,
            self::EMAIL,
            self::STATUS,
            self::AVATAR_URL,
            self::LAST_LOGIN_AT,
            self::ROLES,
            self::PERMISSIONS,
        ];
    }
}

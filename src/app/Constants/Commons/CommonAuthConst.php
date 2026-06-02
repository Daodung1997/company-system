<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonAuthConst
{
    use ConstTrait;

    const GUARD_API = 'api';

    const GUARD_ADMIN = 'admin';

    const TOKEN_TYPE_BEARER = 'Bearer';

    const TOKEN_TYPE = 'token_type'; // Added key

    const ACCESS_TOKEN = 'access_token';

    const REFRESH_TOKEN = 'refresh_token';

    const EXPIRES_IN = 'expires_in';
}

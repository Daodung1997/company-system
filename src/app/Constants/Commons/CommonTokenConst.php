<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonTokenConst
{
    use ConstTrait;

    const ACCESS_TOKEN = 'access_token';

    const TOKEN_TYPE = 'token_type';

    const EXPIRES_IN = 'expires_in';

    const FCM_TOKEN = 'fcm_token';

    const REFRESH_TOKEN = 'refresh_token';
}

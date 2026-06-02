<?php

namespace App\Constants\Master\Models\User;

use App\Traits\ConstTrait;

class UserStatusConst
{
    use ConstTrait;

    const PENDING_VERIFICATION = 'pending_verification';

    const ACTIVE = 'active';

    const BLOCKED = 'blocked';
}

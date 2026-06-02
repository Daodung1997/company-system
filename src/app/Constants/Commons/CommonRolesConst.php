<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonRolesConst
{
    use ConstTrait;

    const ADMIN = 1;

    const EMPLOYEE = 2;

    const CUSTOMER = 3;

    const WORKER = 4;

    const ROLES = [
        self::ADMIN => 'admin',
        self::EMPLOYEE => 'employee',
        self::CUSTOMER => 'customer',
        self::WORKER => 'worker',
    ];
}

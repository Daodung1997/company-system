<?php

namespace App\Constants\Master\Models\UserVerification;

use App\Traits\ConstTrait;

class UserVerificationTypeConst
{
    use ConstTrait;

    const REGISTER = 'register';

    const FORGOT_PASSWORD = 'forgot_password'; // Though usually handled by password_resets, keeping here if needed
}

<?php

namespace App\Constants\Commons;

/**
 * OTP Type Constants
 */
class OtpTypeConst
{
    public const REGISTER = 'register';

    public const FORGOT_PASSWORD = 'forgot_password';

    public const VERIFY_PHONE = 'verify_phone';

    public const VERIFY_EMAIL = 'verify_email';

    public static function getValues(): array
    {
        return [
            self::REGISTER,
            self::FORGOT_PASSWORD,
            self::VERIFY_PHONE,
            self::VERIFY_EMAIL,
        ];
    }
}

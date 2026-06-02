<?php

namespace App\Repositories\Otp;

use App\Models\PasswordReset;
use App\Repositories\Repository;

class OtpRepository extends Repository
{
    public function __construct(PasswordReset $model)
    {
        parent::__construct($model);
    }
}

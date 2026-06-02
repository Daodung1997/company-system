<?php

namespace App\Repositories\UserVerification;

use App\Models\UserVerification;
use App\Repositories\Repository;

class UserVerificationRepository extends Repository
{
    public function __construct(UserVerification $model)
    {
        parent::__construct($model);
    }
}

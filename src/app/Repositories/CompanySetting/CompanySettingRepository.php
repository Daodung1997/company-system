<?php

namespace App\Repositories\CompanySetting;

use App\Models\CompanySetting;
use App\Repositories\Repository;

class CompanySettingRepository extends Repository
{
    public function __construct(CompanySetting $model)
    {
        parent::__construct($model);
    }
}

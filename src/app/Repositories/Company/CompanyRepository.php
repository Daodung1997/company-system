<?php

namespace App\Repositories\Company;

use App\Models\Company;
use App\Repositories\Repository;

class CompanyRepository extends Repository
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }
}

<?php

namespace App\Repositories\ServiceCategory;

use App\Models\ServiceCategory;
use App\Repositories\Repository;

class ServiceCategoryRepository extends Repository
{
    public function __construct(ServiceCategory $model)
    {
        parent::__construct($model);
    }
}

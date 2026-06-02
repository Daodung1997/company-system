<?php

namespace App\Repositories\Area;

use App\Models\Area;
use App\Repositories\Repository;

class AreaRepository extends Repository
{
    public function __construct(Area $model)
    {
        parent::__construct($model);
    }
}

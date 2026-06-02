<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonTable
{
    use ConstTrait;

    const CREATED_BY = 'created_by';

    const UPDATED_BY = 'updated_by';

    const DELETED_AT = 'deleted_at';
}

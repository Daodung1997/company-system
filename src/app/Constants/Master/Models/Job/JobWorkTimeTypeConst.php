<?php

namespace App\Constants\Master\Models\Job;

use App\Traits\ConstTrait;

class JobWorkTimeTypeConst
{
    use ConstTrait;

    public const MORNING = 'MORNING';

    public const AFTERNOON = 'AFTERNOON';

    public const EVENING = 'EVENING';

    public const CUSTOM = 'CUSTOM';
}

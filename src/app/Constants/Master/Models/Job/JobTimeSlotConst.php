<?php

namespace App\Constants\Master\Models\Job;

use App\Traits\ConstTrait;

class JobTimeSlotConst
{
    use ConstTrait;

    public const MORNING_EARLY = '08:00-10:00';

    public const MORNING_LATE = '10:00-12:00';

    public const AFTERNOON_EARLY = '13:00-15:00';

    public const AFTERNOON_LATE = '15:00-17:00';

    public const EVENING_EARLY = '17:00-19:00';

    public const EVENING_LATE = '19:00-21:00';
}

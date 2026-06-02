<?php

namespace App\Constants\Master\Models\WorkerProfile;

class WorkerActivityStatus
{
    use \App\Traits\ConstTrait;

    public const INACTIVE = 'inactive';

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';
}

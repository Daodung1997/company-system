<?php

namespace App\Constants\Master\Models\WorkerProfile;

class WorkerProfileStatus
{
    use \App\Traits\ConstTrait;

    public const INCOMPLETE = 'incomplete';

    public const PENDING = 'pending';

    public const PENDING_APPROVAL = 'pending'; // Alias for compatibility

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';
}

<?php

namespace App\Constants\Master\Models\Complaint;

use App\Traits\ConstTrait;

class ComplaintStatusConst
{
    use ConstTrait;

    public const PENDING = 'pending';

    public const RESOLVED = 'resolved';

    public const REJECTED = 'rejected';
}

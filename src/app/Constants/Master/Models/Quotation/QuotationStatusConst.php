<?php

namespace App\Constants\Master\Models\Quotation;

use App\Traits\ConstTrait;

class QuotationStatusConst
{
    use ConstTrait;

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';
}

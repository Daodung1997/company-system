<?php

namespace App\Constants\Master\Models\Payment;

use App\Traits\ConstTrait;

class PaymentStatusConst
{
    use ConstTrait;

    const PENDING = 'pending';

    const PROCESSING = 'processing';

    const PAID = 'paid';

    const REFUNDED = 'refunded';

    const FAILED = 'failed';

    const CANCELLED = 'cancelled';
}

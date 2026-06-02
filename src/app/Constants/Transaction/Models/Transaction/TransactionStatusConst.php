<?php

namespace App\Constants\Transaction\Models\Transaction;

use App\Traits\ConstTrait;

class TransactionStatusConst
{
    use ConstTrait;

    const PAID = 'PAID';
    const PENDING = 'PENDING';
    const CANCELLED = 'CANCELLED';

    const LABELS = [
        self::PAID => 'Đã thanh toán',
        self::PENDING => 'Chờ thanh toán',
        self::CANCELLED => 'Đã hủy',
    ];
}

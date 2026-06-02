<?php

namespace App\Constants\Master\Models\Job;

use App\Traits\ConstTrait;

class JobStatusConst
{
    use ConstTrait;

    public const WAITING_FOR_QUOTATION = 'waiting_for_quotation';

    public const QUOTED = 'quoted';

    public const PENDING_PAYMENT = 'pending_payment';

    public const PAID = 'paid';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const COMPLAINT = 'complaint';

    public const REFUNDED = 'refunded';

    public const CANCELLED = 'cancelled';

    public const EXPIRED = 'expired';

    /**
     * Statuses for "Đang xử lý" tab (worker)
     */
    public static function inProgressStatuses(): array
    {
        return [
            self::QUOTED,
            self::PENDING_PAYMENT,
            self::PAID,
            self::IN_PROGRESS,
            self::COMPLAINT,
        ];
    }

    /**
     * Statuses for "Đã kết thúc" tab (worker & customer)
     */
    public static function completedStatuses(): array
    {
        return [
            self::COMPLETED,
            self::REFUNDED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    /**
     * Statuses for "Đang yêu cầu" tab (customer)
     */
    public static function customerRequestingStatuses(): array
    {
        return [
            self::WAITING_FOR_QUOTATION,
            self::QUOTED,
        ];
    }

    /**
     * Statuses for "Đang thực hiện" tab (customer)
     */
    public static function customerInProgressStatuses(): array
    {
        return [
            self::PENDING_PAYMENT,
            self::PAID,
            self::IN_PROGRESS,
            self::COMPLAINT,
        ];
    }

    /**
     * Statuses for "Đang hoạt động / Chờ báo giá" tab (customer)
     */
    public static function customerActiveStatuses(): array
    {
        return [
            self::WAITING_FOR_QUOTATION,
            self::QUOTED,
            self::PENDING_PAYMENT,
            self::PAID,
            self::IN_PROGRESS,
        ];
    }

    /**
     * Statuses for "Lịch sử" tab (customer)
     */
    public static function customerHistoryStatuses(): array
    {
        return [
            self::COMPLETED,
            self::COMPLAINT,
            self::REFUNDED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }
}

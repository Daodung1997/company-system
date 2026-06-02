<?php

namespace App\Constants\Master\Models\Notification;

class NotificationTypeConst
{
    public const SYSTEM = 'system';

    public const JOB_COMPLAINT = 'job_complaint';

    public const JOB_INVITATION = 'job_invitation';

    public const JOB_QUOTATION_RECEIVED = 'job_quotation_received';

    public const JOB_QUOTATION_ACCEPTED = 'job_quotation_accepted';

    public const JOB_QUOTATION_REJECTED = 'job_quotation_rejected';

    public const JOB_STARTED = 'job_started';

    public const JOB_COMPLETED = 'job_completed';

    public const JOB_PAID = 'job_paid';

    public const JOB_CANCELLED = 'job_cancelled';

    public const NEW_JOB_NEARBY = 'new_job_nearby';

    public const WITHDRAWAL_SUCCESS = 'withdrawal_success';

    public const RATING_RECEIVED = 'rating_received';

    public const NEW_MESSAGE = 'new_message';

    public const WITHDRAWAL_FAILED = 'withdrawal_failed';

    public const WORKER_PROFILE_APPROVED = 'worker_profile_approved';

    public const AREA_EXPANSION_SUGGESTION = 'area_expansion_suggestion';

    public const COMMISSION_POLICY_UPDATE = 'commission_policy_update';

    public static function getAll()
    {
        return [
            self::SYSTEM,
            self::JOB_COMPLAINT,
            self::JOB_INVITATION,
            self::JOB_QUOTATION_RECEIVED,
            self::JOB_QUOTATION_ACCEPTED,
            self::JOB_QUOTATION_REJECTED,
            self::JOB_STARTED,
            self::JOB_COMPLETED,
            self::JOB_PAID,
            self::JOB_CANCELLED,
            self::NEW_JOB_NEARBY,
            self::WITHDRAWAL_SUCCESS,
            self::RATING_RECEIVED,
            self::NEW_MESSAGE,
            self::WITHDRAWAL_FAILED,
            self::WORKER_PROFILE_APPROVED,
            self::AREA_EXPANSION_SUGGESTION,
            self::COMMISSION_POLICY_UPDATE,
        ];
    }
}

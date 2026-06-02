<?php

namespace App\Constants\Master\Models\WorkerDocument;

class WorkerDocumentStatusConst
{
    const PENDING = 'pending';

    const VERIFIED = 'verified';

    const REJECTED = 'rejected';

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::VERIFIED,
            self::REJECTED,
        ];
    }
}

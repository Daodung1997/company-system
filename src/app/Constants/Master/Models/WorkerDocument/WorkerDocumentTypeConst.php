<?php

namespace App\Constants\Master\Models\WorkerDocument;

class WorkerDocumentTypeConst
{
    use \App\Traits\ConstTrait;

    const CCCD = 'cccd';

    const CMND = 'cmnd';

    const PASSPORT = 'passport';

    const DRIVING_LICENSE = 'driving_license';

    public const LABELS = [
        self::CCCD => 'Căn cước công dân',
        self::CMND => 'Chứng minh nhân dân',
        self::PASSPORT => 'Hộ chiếu',
        self::DRIVING_LICENSE => 'Giấy phép lái xe',
    ];
}

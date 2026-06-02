<?php

namespace App\Constants\Commons;

class File
{
    const MAX_NUMBER_UPLOAD = 8;

    const MAX_NUMBER_UPLOAD_AVATAR = 1;

    const MAX_SIZE_UPLOAD = 102400; // 10Mb

    const MAX_SIZE_UPLOAD_FILE = 204800; // 200Mb

    const STATUS_DRAFT = 0;

    const STATUS_IN_USE = 1;

    const PARAM_SINGLE_IMAGE = 'image';

    const PARAM_MULTI_IMAGE = 'images';

    const PARAM_FILE = 'file';

    const PARAM_FILE_MULTI = 'files';

    const IMAGE_FILE_EXTENSION = ['jpg', 'jpeg', 'png'];

    const PDF_FILE_EXTENSION = ['pdf'];

    const FILE_PDF_EXIST = [
        'existed' => '有り',
        'not_existed' => '無し',
    ];
}

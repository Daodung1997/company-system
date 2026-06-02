<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonHelperConst
{
    use ConstTrait;

    const SJIS = 'SJIS';

    const SJIS_WIN = 'SJIS-win';

    const SJIS_MAC = 'SJIS-mac';

    const UTF_8 = 'UTF-8';

    const ISO_8859_1 = 'ISO-8859-1';

    const UTF_16LE = 'utf-16le';

    const EXCEL_IMPORTS_CSV_INPUT_ENCODING = 'excel.imports.csv.input_encoding';
}

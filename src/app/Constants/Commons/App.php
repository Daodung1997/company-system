<?php

namespace App\Constants\Commons;

class App
{
    const REGEX_PASSWORD = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&-])[A-Za-z\d@$!%*#?&-]{8,255}$/';

    const DATE_FORMAT = 'Ymd';

    const DATETIME_FORMAT_MINUTE = 'Y-m-d H:i';

    const TIMEZONE_JAPAN = 'Asia/Tokyo';

    const STATUS = 'status';

    const PER_PAGE = 10;

    const FLAG_TRUE = 1;

    const FLAG_FALSE = 0;

    const MAX_PRICE = 1000000000;

    const MAX_QUANTITY = 1000000000;

    const DATE_FORMAT_CALENDAR = 'Y-m-d';

    const MAX_NOTE = 2000;

    const ALPHANUM = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const LOWER_CASE_ALPHA = 'abcdefghijklmnopqrstuvwxyz';

    const NUMBER = '0123456789';

    const SPECIAL = '@$!%*#?&';

    const MAX_TWENTY = 20;

    const MAX_FIFTY = 50;

    const MAX_TWO_HUNDRED_FIFTY_FIVE = 255;

    const MIMES = 'jpeg,png,jpg,gif,svg';

    const MAX_UPLOAD_IMAGE = '2048';

    const MAX_EIGHT = 8;

    const REGEX_SUPPLIER_NAME = '/^[\p{L}0-9\s]+$/u';

    const REGEX_TAX_CODE = '/^[0-9]{10,13}$/';

    const REGEX_PHONE = '/^\+?[0-9]{7,20}$/';

    const REGEX_VOUCHER_CODE = '/^[A-Za-z0-9_-]+$/';

    const REGEX_DEDUCTION = '/^\d+(\.\d{1,2})?$/';

    const REGEX_NAME = '/^[\p{L}0-9\s]+$/u';
}

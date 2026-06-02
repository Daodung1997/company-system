<?php

namespace App\Constants\Commons;

class Rule
{
    const EXISTS = 'exists';

    const UNIQUE = 'unique';

    const IN = 'in';

    const NOT_IN = 'notIn';

    const BETWEEN = 'between';

    const MIN = 'min';

    const MAX = 'max';

    const REGEXP = 'regex';

    const SIZE = 'size';

    const EMAIL = 'email';

    const URL = 'url';

    const ALPHA_NUM = 'alpha_num';

    const ALPHA = 'alpha';

    const BOOLEAN = 'boolean';

    const DATE = 'date';

    const DATE_TIME = 'datetime';

    const CONFIRMED = 'confirmed';

    const NUMERIC = 'numeric';

    const REQUIRED = 'required';

    const REQUIRED_IF = 'requiredIf';

    const CONTAINS = 'contains';

    const LIST_RULES = [
        self::EXISTS,
        self::UNIQUE,
        self::IN,
        self::NOT_IN,
        self::BETWEEN,
        self::MIN,
        self::MAX,
        self::REGEXP,
        self::SIZE,
        self::EMAIL,
        self::URL,
        self::ALPHA_NUM,
        self::ALPHA,
        self::BOOLEAN,
        self::DATE,
        self::CONFIRMED,
        self::NUMERIC,
        self::REQUIRED,
        self::REQUIRED_IF,
        self::CONTAINS,
    ];
}

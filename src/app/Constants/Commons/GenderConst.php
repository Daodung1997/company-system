<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class GenderConst
{
    use ConstTrait;

    // Integer constants mapped to DB
    public const MALE = 1;

    public const FEMALE = 2;

    public const OTHER = 3;

    // String mappings for API
    public const STR_MALE = 'male';

    public const STR_FEMALE = 'female';

    public const STR_OTHER = 'other';

    public static function getStringMappings(): array
    {
        return [
            self::STR_MALE => self::MALE,
            self::STR_FEMALE => self::FEMALE,
            self::STR_OTHER => self::OTHER,
        ];
    }

    public static function getIntegerMappings(): array
    {
        return [
            self::MALE => self::STR_MALE,
            self::FEMALE => self::STR_FEMALE,
            self::OTHER => self::STR_OTHER,
        ];
    }

    public static function toInteger(?string $genderStr): ?int
    {
        $mappings = self::getStringMappings();

        return $mappings[$genderStr] ?? null;
    }

    public static function toString(?int $genderInt): ?string
    {
        $mappings = self::getIntegerMappings();

        return $mappings[$genderInt] ?? null;
    }

    public static function getValidStrings(): array
    {
        return [self::STR_MALE, self::STR_FEMALE, self::STR_OTHER];
    }
}

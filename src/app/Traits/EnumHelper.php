<?php

namespace App\Traits;

trait EnumHelper
{
    /**
     * Lấy tất cả các giá trị của enum.
     */
    public static function getValues(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Lấy tất cả các tên của enum.
     */
    public static function getNames(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * Kiểm tra giá trị có hợp lệ với enum hay không.
     */
    public static function isValidValue(mixed $value): bool
    {
        return in_array($value, static::getValues());
    }

    /**
     * Lấy enum từ giá trị.
     */
    public static function fromValue(mixed $value): ?self
    {
        return static::tryFrom($value) ?: null;
    }

    /**
     * Lấy giá trị của enum từ tên.
     *
     * @return mixed|null
     */
    public static function fromName(string $name): mixed
    {
        return constant("self::$name") ?? null;
    }

    /**
     * Lấy tên enum từ giá trị.
     */
    public static function getNameFromValue(mixed $value): ?string
    {
        $enum = static::fromValue($value);

        return $enum?->name;
    }

    /**
     * Lấy giá trị enum từ tên.
     *
     * @return mixed|null
     */
    public static function getValueFromName(string $name): mixed
    {
        return constant("self::$name") ?? null;
    }
}

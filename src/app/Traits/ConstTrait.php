<?php

namespace App\Traits;

use ReflectionClass;

trait ConstTrait
{
    public static function getConstants(): array
    {
        $reflection = new ReflectionClass(static::class);
        $constants = [];

        foreach ($reflection->getReflectionConstants() as $constant) {
            $name = $constant->getName();
            // Filter out internal constants
            if ($name === 'LABELS') {
                continue;
            }
            $constants[$name] = $constant->getValue();
        }

        return $constants;
    }

    public static function getValues(): array
    {
        $reflection = new ReflectionClass(static::class);

        $values = [];
        foreach ($reflection->getReflectionConstants() as $constant) {
            $name = $constant->getName();
            // Filter out internal constants
            if ($name === 'LABELS') {
                continue;
            }
            $values[] = $constant->getValue();
        }

        return array_values(array_unique($values));
    }

    public static function getLabels(): array
    {
        return defined('static::LABELS') ? static::LABELS : [];
    }
}

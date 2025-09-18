<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use UnitEnum;

if (! function_exists('HosmelQ\Fault\enum_value')) {
    /**
     * Normalize enum values to strings.
     *
     * @internal
     */
    function enum_value(BackedEnum|string|UnitEnum $value): string
    {
        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value,
        };
    }
}

<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use UnitEnum;

/**
 * Resolve a scalar representation for an enum-like value.
 *
 * @internal
 */
function enum_value(mixed $value): mixed
{
    return match (true) {
        $value instanceof BackedEnum => $value->value,
        $value instanceof UnitEnum => $value->name,
        default => $value,
    };
}

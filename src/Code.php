<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use Stringable;
use UnitEnum;

final readonly class Code implements Stringable
{
    /**
     * Prefix used for every generated URN.
     */
    private const PREFIX = 'err';

    /**
     * Cache the individual segments of the URN.
     */
    private function __construct(
        private string $system,
        private string $category,
        private string $specific,
    ) {
    }

    /**
     * Build a Code object from enum or string segments.
     */
    public static function make(BackedEnum|string|UnitEnum $system, BackedEnum|string|UnitEnum $category, BackedEnum|string|UnitEnum $specific): self
    {
        return new self(
            enum_value($system),
            enum_value($category),
            enum_value($specific),
        );
    }

    /**
     * Return the code as a URN string.
     */
    public function urn(): string
    {
        return implode(':', [self::PREFIX, $this->system, $this->category, $this->specific]);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->urn();
    }
}

<?php

declare(strict_types=1);

use function HosmelQ\Fault\enum_value;

use HosmelQ\Fault\Tests\TestSupport\IntBackedEnum;
use HosmelQ\Fault\Tests\TestSupport\StringBackedEnum;
use HosmelQ\Fault\Tests\TestSupport\UnitEnum;

it('resolves backed enum values to their backing scalar', function (): void {
    expect(enum_value(IntBackedEnum::NotFound))->toBe(404)
        ->and(enum_value(StringBackedEnum::Failure))->toBe('failure');
});

it('resolves unit enums to their case name', function (): void {
    expect(enum_value(UnitEnum::Warning))->toBe('Warning');
});

it('returns non-enum inputs as-is', function (): void {
    expect(enum_value('plain'))->toBe('plain');
});

<?php

declare(strict_types=1);

use function HosmelQ\Fault\enum_value;

enum TestBackedEnum: string
{
    case Foo = 'foo';
}

enum TestUnitEnum
{
    case Bar;
}

it('returns the scalar value from a backed enum', function (): void {
    expect(enum_value(TestBackedEnum::Foo))->toBe('foo');
});

it('returns the name from a unit enum', function (): void {
    expect(enum_value(TestUnitEnum::Bar))->toBe('Bar');
});

it('returns the original value for string inputs', function (): void {
    expect(enum_value('plain'))->toBe('plain');
});

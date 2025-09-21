<?php

declare(strict_types=1);

use HosmelQ\Fault\FaultException;
use HosmelQ\Fault\Tests\TestSupport\IntBackedEnum;
use HosmelQ\Fault\Tests\TestSupport\StringBackedEnum;
use HosmelQ\Fault\Tests\TestSupport\UnitEnum;
use HosmelQ\Fault\Wrap;

it('sets code from scalars and enums', function (): void {
    $fault = new FaultException();

    $scalar = Wrap::code(500);

    $scalar($fault);

    expect($fault->code())->toBe(500);

    $enum = Wrap::code(IntBackedEnum::NotFound);

    $enum($fault);

    expect($fault->code())->toBe(404);

    $unit = Wrap::code(UnitEnum::Warning);

    $unit($fault);

    expect($fault->code())->toBe('Warning');
});

it('adds internal messages from enums and strings', function (): void {
    $fault = new FaultException();

    $enumWrapper = Wrap::internal(StringBackedEnum::Failure);
    $stringWrapper = Wrap::internal('first');

    $enumWrapper($fault);
    $stringWrapper($fault);

    expect($fault->internalMessages)->toBe([
        'first',
        'failure',
    ]);
});

it('adds public messages from enums and strings', function (): void {
    $fault = new FaultException();

    $enumWrapper = Wrap::public(UnitEnum::Warning);
    $stringWrapper = Wrap::public('first');

    $enumWrapper($fault);
    $stringWrapper($fault);

    expect($fault->publicMessages)->toBe([
        'first',
        'Warning',
    ]);
});

it('merges context from array, iterable, and callable providers; ignores non-iterable results', function (): void {
    $fault = new FaultException();

    $arrayContext = Wrap::context(['foo' => 'bar']);
    $callableContext = Wrap::context(static fn (): array => ['alpha' => 'beta']);
    $iterableContext = Wrap::context(new ArrayIterator(['baz' => 'qux']));
    $nullContext = Wrap::context(static fn (): null => null);

    $arrayContext($fault);
    $callableContext($fault);
    $iterableContext($fault);
    $nullContext($fault);

    expect($fault->context)->toBe([
        'foo' => 'bar',
        'alpha' => 'beta',
        'baz' => 'qux',
    ]);
});

it('adds keyed context from string keys and skips invalid keys', function (): void {
    $fault = new FaultException();

    $invalid = Wrap::context('', 'ignored');
    $keyed = Wrap::context('valid', 'value');

    $invalid($fault);
    $keyed($fault);

    expect($fault->context)->toBe([
        'valid' => 'value',
    ]);
});

it('captures origin at wrapper creation', function (): void {
    [$wrapper, $expected] = makeOriginWrapper();

    $fault = new FaultException();

    $wrapper($fault);

    expect($fault->origin)->toBe($expected);
});

function makeOriginWrapper(): array
{
    $wrapper = Wrap::origin();

    $probe = new FaultException();

    $wrapper($probe);

    return [$wrapper, $probe->origin];
}

<?php

declare(strict_types=1);

use HosmelQ\Fault\FaultException;

it('initializes empty when no message provided', function (): void {
    $fault = new FaultException();

    expect($fault->internalMessages)->toBe([])
        ->and($fault->getMessage())->toBe('');
});

it('initializes with internal message when provided', function (): void {
    $fault = new FaultException('initial message');

    expect($fault->internalMessages)->toBe(['initial message'])
        ->and($fault->getMessage())->toBe('initial message');
});

it('prepends internal messages and refreshes concatenated message', function (): void {
    $fault = new FaultException('base');

    $fault->addInternal('middle');
    $fault->addInternal('top');

    expect($fault)
        ->internalMessages->toBe(['top', 'middle', 'base'])
        ->getMessage()->toBe('top: middle: base');
});

it('prepends public messages without affecting internal message chain', function (): void {
    $fault = new FaultException('internal');

    $fault->addPublic('first public');
    $fault->addPublic('second public');

    expect($fault)
        ->publicMessages->toBe(['second public', 'first public'])
        ->getMessage()->toBe('internal');
});

it('ignores empty internal and public messages', function (): void {
    $fault = new FaultException();

    $fault->addInternal('');
    $fault->addPublic('');

    expect($fault->internalMessages)->toBe([])
        ->and($fault->publicMessages)->toBe([])
        ->and($fault->getMessage())->toBe('');
});

it('filters context keys and overwrites existing entries', function (): void {
    $fault = new FaultException();

    $fault->addContext([
        'a' => '1',
        '' => 'ignored',
        23 => 'skipped',
    ]);

    $fault->addContext([
        'b' => '2',
        'a' => '3',
    ]);

    expect($fault->context)->toBe([
        'a' => '3',
        'b' => '2',
    ]);
});

it('validates origin input before storing location', function (): void {
    $fault = new FaultException();

    $fault->setOrigin([
        'file' => '',
        'line' => 25,
    ]);

    expect($fault->origin)->toBeNull();

    $fault->setOrigin([
        'file' => __FILE__,
        'line' => 0,
    ]);

    expect($fault->origin)->toBeNull();

    $line = __LINE__ + 3;

    $fault->setOrigin([
        'file' => __FILE__,
        'line' => $line,
    ]);

    expect($fault->origin)->toBe([
        'file' => __FILE__,
        'line' => $line,
    ]);
});

it('exposes assigned codes through the code accessor', function (): void {
    $fault = new FaultException();

    $fault->setCode('first_code');

    expect($fault->code())->toBe('first_code');

    $fault->setCode('second_code');

    expect($fault->code())->toBe('second_code');
});

it('includes previous fault chain and plain throwables when refreshing message', function (): void {
    $root = new FaultException('root');
    $intermediate = new FaultException('intermediate', $root);

    $manual = new FaultException('', new Exception('plain'));

    $manual->addInternal('manual');

    expect($intermediate->getMessage())->toBe('intermediate: root')
        ->and($manual->getMessage())->toBe('manual: plain');
});

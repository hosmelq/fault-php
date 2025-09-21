<?php

declare(strict_types=1);

use HosmelQ\Fault\AggregateFault;
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\FaultException;
use HosmelQ\Fault\Tests\TestSupport\StringBackedEnum;
use HosmelQ\Fault\Wrap;

it('creates a new fault with internal message and applies wrappers', function (): void {
    $fault = Fault::new(
        'failure',
        Wrap::public('User facing'),
        Wrap::internal('Developer note'),
        Wrap::code(404),
    );

    expect($fault)->toBeInstanceOf(FaultException::class)
        ->and(Fault::internals($fault))->toBe(['Developer note', 'failure'])
        ->and(Fault::publicMessages($fault))->toBe(['User facing'])
        ->and(Fault::code($fault))->toBe(404);
});

it('accepts enums as internal message input', function (): void {
    $fault = Fault::new(StringBackedEnum::Failure);

    expect(Fault::internals($fault))->toBe(['failure']);
});

it('wraps an existing throwable and applies wrappers', function (): void {
    $previous = new Exception('base');
    $fault = Fault::wrap(
        $previous,
        Wrap::public('visible'),
        Wrap::internal('hidden'),
    );

    expect($fault->getPrevious())->toBe($previous)
        ->and(Fault::internals($fault))->toBe(['hidden', 'base'])
        ->and(Fault::publicMessages($fault))->toBe(['visible']);
});

it('combines multiple throwables into aggregate fault', function (): void {
    $first = Fault::new('first');
    $second = Fault::new('second');

    $combined = Fault::combine([$first, $second], Wrap::public('combined'));
    $previous = $combined->getPrevious();

    expect($combined)->toBeInstanceOf(FaultException::class)
        ->and($previous)->toBeInstanceOf(AggregateFault::class)
        ->and(iterator_to_array($previous))->toBe([$first, $second])
        ->and(Fault::publicMessages($combined))->toBe(['combined']);
});

it('resolves the newest code across nested fault chain', function (): void {
    $root = Fault::new('root', Wrap::code(100));
    $wrapped = Fault::wrap($root, Wrap::code(200));

    expect(Fault::code($wrapped))->toBe(200);
});

it('collects context newest-first without overwriting existing keys', function (): void {
    $root = Fault::new('root', Wrap::context(['a' => '1', 'b' => '2']));
    $wrapped = Fault::wrap($root, Wrap::context(['b' => '3', 'c' => '4']));

    expect(Fault::context($wrapped))->toBe([
        'b' => '3',
        'c' => '4',
        'a' => '1',
    ]);
});

it('collects internal messages including plain previous throwables', function (): void {
    $plain = new Exception('base');
    $fault = Fault::wrap($plain, Wrap::internal('middle'));
    $fault = Fault::wrap($fault, Wrap::internal('top'));

    expect(Fault::internals($fault))->toBe([
        'top',
        'middle',
        'base',
    ]);
});

it('collects unique origins newest-first', function (): void {
    $base = Fault::new('root');
    $first = Fault::wrap($base, static function (FaultException $fault): void {
        $fault->setOrigin([
            'file' => 'a.php',
            'line' => 10,
        ]);
    });

    $second = Fault::wrap($first, static function (FaultException $fault): void {
        $fault->setOrigin([
            'file' => 'b.php',
            'line' => 20,
        ]);
    });

    $third = Fault::wrap($second, static function (FaultException $fault): void {
        $fault->setOrigin([
            'file' => 'a.php',
            'line' => 10,
        ]);
    });

    $origins = Fault::origins($third);

    expect($origins)->toBe([
        ['file' => 'a.php', 'line' => 10],
        ['file' => 'b.php', 'line' => 20],
    ]);
});

it('collects public messages in order and builds user message', function (): void {
    $fault = Fault::new('internal', Wrap::public('first'));
    $fault = Fault::wrap($fault, Wrap::public('second'));

    expect(Fault::publicMessages($fault))->toBe([
        'second',
        'first',
    ])->and(Fault::userMessage($fault))->toBe('second first');
});

it('traverses depth-first without revisiting duplicate throwables', function (): void {
    $root = Fault::new('root');
    $middle = Fault::wrap($root, Wrap::internal('middle'));
    $leaf = Fault::wrap($middle, Wrap::internal('leaf'));

    $combined = Fault::combine([$leaf, $leaf], Wrap::internal('combined'));

    expect(Fault::internals($combined))->toBe([
        'combined',
        'leaf',
        'middle',
        'root',
    ]);
});

it('handles plain throwables without fault layers gracefully', function (): void {
    $plain = new Exception('plain error');

    expect(Fault::code($plain))->toBeNull()
        ->and(Fault::context($plain))->toBe([])
        ->and(Fault::publicMessages($plain))->toBe([])
        ->and(Fault::userMessage($plain))->toBe('')
        ->and(Fault::origins($plain))->toBe([]);
});

it('resolves null code when no layers define a code', function (): void {
    $fault = Fault::new('no code');

    expect(Fault::code($fault))->toBeNull();
});

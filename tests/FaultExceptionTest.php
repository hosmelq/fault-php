<?php

declare(strict_types=1);

use HosmelQ\Fault\FaultException;

it('ignores blank internal messages', function (): void {
    $fault = new FaultException('Seed message');
    $fault->addInternal('   ');

    expect($fault->getInternalChain())->toBe(['Seed message']);
});

it('ignores blank public messages', function (): void {
    $fault = new FaultException('Seed message');
    $fault->addPublic('   ');

    expect($fault->userFacingMessage())->toBe('');
});

it('returns null when no code is assigned in the chain', function (): void {
    $fault = new FaultException('Seed message');

    expect($fault->code())->toBeNull();
});

it('inherits codes from previous fault layers', function (): void {
    $inner = new FaultException('Inner');
    $inner->setCode('err:demo:inner:code');

    $outer = new FaultException('Outer', $inner);

    expect($outer->code())->toBe('err:demo:inner:code');
});

it('merges internal messages from non-fault throwables', function (): void {
    $previous = new RuntimeException('database timeout');
    $fault = new FaultException('', $previous);

    expect($fault->getInternalChain())->toBe(['database timeout']);
});

it('merges public messages across the chain', function (): void {
    $inner = new FaultException('Inner');
    $inner->addPublic('Inner public');

    $outer = new FaultException('Outer', $inner);
    $outer->addPublic('Outer public');

    expect($outer->getPublicChain())->toBe(['Outer public', 'Inner public']);
});

it('ignores empty codes and preserves the first recorded code', function (): void {
    $fault = new FaultException('Inner');
    $fault->setCode('   ');
    $fault->setCode('err:demo:inner:first');
    $fault->setCode('err:demo:inner:second');

    expect($fault->code())->toBe('err:demo:inner:first');
});

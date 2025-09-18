<?php

declare(strict_types=1);

use HosmelQ\Fault\Code;
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\FaultException;

it('creates a new fault with fluent wrappers', function (): void {
    $fault = Fault::new(
        'Token request rejected with 400',
        Fault::internal('Required field `client_id` was missing from the payload'),
        Fault::public('Please provide a client identifier.'),
        Fault::code('err:user:bad_request:missing_client_id'),
    );

    expect($fault)
        ->toBeInstanceOf(FaultException::class)
        ->and($fault->getMessage())
        ->toBe('Required field `client_id` was missing from the payload: Token request rejected with 400')
        ->and($fault->userFacingMessage())
        ->toBe('Please provide a client identifier.')
        ->and($fault->code())
        ->toBe('err:user:bad_request:missing_client_id');
});

it('wraps existing throwables while preserving chain context', function (): void {
    $base = new RuntimeException('validation error');

    $fault = Fault::wrap(
        $base,
        Fault::code(Code::make('user', 'bad_request', 'missing_client_id')),
        Fault::internal('Payload schema rejected request'),
        Fault::public('Please provide a client identifier.'),
        Fault::internal('Validation aborted before token minting'),
        Fault::public('Submit a valid client identifier before retrying.'),
    );

    expect($fault)
        ->toBeInstanceOf(FaultException::class)
        ->and($fault->getMessage())
        ->toBe('Validation aborted before token minting: Payload schema rejected request: validation error')
        ->and($fault->userFacingMessage())
        ->toBe('Submit a valid client identifier before retrying. Please provide a client identifier.')
        ->and(Fault::codeFor($fault))
        ->toBe('err:user:bad_request:missing_client_id');
});

it('returns null when wrapping a null throwable', function (): void {
    expect(Fault::wrap(null))->toBeNull();
});

it('returns the first code found within a fault chain', function (): void {
    $root = Fault::new('Missing client identifier', Fault::code('err:user:bad_request:missing_client_id'));
    $middle = Fault::wrap($root, Fault::internal('Request validation aborted'));
    $top = Fault::wrap($middle, Fault::internal('Authorization flow stopped execution'));

    expect(Fault::codeFor($top))->toBe('err:user:bad_request:missing_client_id');
});

it('resolves the first user-facing message in the chain', function (): void {
    $inner = Fault::new('Validation failed', Fault::public('Please provide a client identifier.'));
    $outer = Fault::wrap($inner, Fault::internal('Client payload rejected'));

    expect(Fault::userFacingMessage($outer))->toBe('Please provide a client identifier.');
});

it('returns null when no fault code exists in the chain', function (): void {
    $nonFault = new RuntimeException('plain exception');

    expect(Fault::codeFor($nonFault))->toBeNull();
});

it('returns an empty string when no public messages exist', function (): void {
    $fault = Fault::new('Only internal details');

    expect(Fault::userFacingMessage($fault))->toBe('');
});

<?php

declare(strict_types=1);

use HosmelQ\Fault\Code;

it('allows colon characters inside segments', function (): void {
    $code = Code::make('system', 'category:with:colon', 'specific');

    expect((string) $code)->toBe('err:system:category:with:colon:specific');
});

it('casts codes to their URN representation', function (): void {
    $code = Code::make('system', 'category', 'specific');

    expect((string) $code)->toBe('err:system:category:specific');
});

it('accepts empty segments when provided explicitly', function (): void {
    $code = Code::make('', '', 'specific');

    expect((string) $code)->toBe('err:::specific');
});

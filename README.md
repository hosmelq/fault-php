# fault-php

Structured fault exceptions for consistent error reporting in PHP 8.2+.

## Introduction

fault-php helps you wrap any throwable in a `FaultException` that carries internal diagnostics, user-facing messages, and URN-style error identifiers. Wrappers let you progressively enrich errors without losing the original context or stack trace.

```php
<?php

use HosmelQ\Fault\Fault;

throw Fault::new(
    'Token request rejected with 400',
    Fault::internal('Required field `client_id` was missing from the payload'),
    Fault::public('Please provide a client identifier.'),
    Fault::code('err:user:bad_request:missing_client_id'),
);
```

This exception stores everything you need to log, render user feedback, and decide how to respond—while keeping the underlying throwable attached.

## Requirements

- PHP 8.2+

## Installation & setup

You can install the package via composer:

```bash
composer require hosmelq/fault-php
```

## Basic usage

### Getting started

`Fault::new()` creates a fresh `FaultException` with an internal diagnostic message. You can pass additional wrappers to attach public messaging and structured identifiers in a single call.

```php
<?php

use HosmelQ\Fault\Fault;
use HosmelQ\Fault\FaultException;

try {
    throw Fault::new(
        'Token request rejected with 400',
        Fault::internal('Required field `client_id` was missing from the payload'),
        Fault::public('Please provide a client identifier.'),
        Fault::code('err:user:bad_request:missing_client_id'),
    );
} catch (FaultException $fault) {
    error_log($fault->getMessage());

    echo $fault->userFacingMessage(); // Please provide a client identifier.
}
```

The internal message aggregates newest-first diagnostics for logging, while `userFacingMessage()` concatenates public messages for UI display.

### Wrapping existing exceptions

Use `Fault::wrap()` when you catch an exception and need to add structured context before rethrowing. The original throwable stays in the chain for stack tracing and root-cause inspection.

```php
<?php

use HosmelQ\Fault\Code;
use HosmelQ\Fault\Fault;
use Throwable;

try {
    $authenticator->createSession($request);
} catch (Throwable $throwable) {
    throw Fault::wrap(
        $throwable,
        Fault::code(Code::make('user', 'bad_request', 'missing_client_id')),
        Fault::internal('Payload schema rejected request'),
        Fault::public('Please provide a client identifier.'),
        Fault::internal('Validation aborted before token minting'),
        Fault::public('Submit a valid client identifier before retrying.'),
    );
}
```

When the input is `null`, `Fault::wrap()` simply returns `null`, making it safe to use with optional throwables.

### Extracting fault metadata

Utility helpers let you recover structured data from any throwable chain, whether it originated in your code or deeper in the stack.

```php
<?php

use HosmelQ\Fault\Fault;
use Throwable;

function renderError(Throwable $throwable): array
{
    return [
        'code' => Fault::codeFor($throwable),
        'message' => Fault::userFacingMessage($throwable),
    ];
}
```

`Fault::codeFor()` fetches the first URN code found, while `Fault::userFacingMessage()` resolves the first non-empty public message ready for display.

## Managing fault codes

`HosmelQ\Fault\Code` builds URN-style identifiers that communicate which system, category, and specific failure occurred. You can compose codes with enums to keep the vocabulary consistent across your services.

```php
<?php

use HosmelQ\Fault\Code;

enum System: string
{
    case User = 'user';
}

enum Category: string
{
    case BadRequest = 'bad_request';
}

enum Specific: string
{
    case MissingClientId = 'missing_client_id';
}

$code = Code::make(System::User, Category::BadRequest, Specific::MissingClientId);

$urn = $code->urn();      // err:user:bad_request:missing_client_id
(string) $code;           // err:user:bad_request:missing_client_id
```

Codes tolerate empty or enum-backed segments, letting you tailor the granularity of identifiers to your tracking needs.

## Testing

```bash
composer test
```

## Changelog

All notable changes to fault-php are documented [on GitHub](https://github.com/hosmelq/fault-php/blob/main/CHANGELOG.md).

## Credits

- [Hosmel Quintana](https://github.com/hosmelq)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

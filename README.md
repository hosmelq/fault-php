# Fault PHP

Composable helpers for wrapping and aggregating PHP exceptions with codes, context, origins, and clear internal and user messages.

## Introduction

Wrap errors with rich, structured metadata and traverse entire exception chains ergonomically. Attach user‑facing and internal messages, codes, call‑site origins, and context, then extract them consistently for logging and display.

```php
use Throwable;
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\Wrap;

try {
    // Some operation that can fail
} catch (Throwable $error) {
    // Wrap with user + internal messages, a code, and context
    $fault = Fault::wrap(
        $error,
        Wrap::code('err:stripe:payment:insufficient_funds'),
        Wrap::internal('Stripe API returned 402: insufficient_funds'),
        Wrap::public('Your payment could not be processed. Please try again later.'),
        Wrap::context(['customer_id' => 'cust_123', 'payment_method' => 'card_456']),
        Wrap::origin(),
    );

    throw $fault;  // Re‑throw enriched error
}

// Somewhere higher in the stack
try {
    // Application entrypoint, controller action, etc.
} catch (Throwable $error) {
    // Aggregate data across the whole chain
    $message = Fault::userMessage($error);  // "Your payment could not be processed. Please try again later."
    $code = Fault::code($error);            // "err:stripe:payment:insufficient_funds"
    $ctx = Fault::context($error);          // ['customer_id' => 'cust_123', 'payment_method' => 'card_456']
    $intern = Fault::internals($error);     // ["Stripe API returned 402: insufficient_funds", "... base error ..."]
    $origins = Fault::origins($error);      // [[file => '...', line => 123], ...]
}
```

It produces a standard `FaultException` layer you can re‑throw and later traverse to extract codes, messages, context, and origins across nested exceptions.

## Requirements

- PHP 8.4+

## Installation & setup

Install the package via composer:

```bash
composer require hosmelq/fault-php
```

## Basic usage

### Getting started

Create a new fault with an internal message and attach wrappers for public text, codes, and context:

```php
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\Wrap;

$fault = Fault::new(
    'Email validation failed',  // internal message (developer‑facing)
    Wrap::public('Please check your email address.'),
    Wrap::code('err:validation:email:invalid_format'),
    Wrap::context(['field' => 'email', 'value' => 'invalid@']),
);

// Extract values across the chain
Fault::userMessage($fault);  // "Please check your email address."
Fault::internals($fault);    // ["Email validation failed"]
Fault::code($fault);         // "err:validation:email:invalid_format"
Fault::context($fault);      // ['field' => 'email', 'value' => 'invalid@']
```

### Wrapping existing throwables

Wrap any existing `Throwable` to add fault metadata without losing the original exception:

```php
use Throwable;
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\Wrap;

try {
    // Code that might throw
} catch (Throwable $error) {
    $fault = Fault::wrap(
        $error,
        Wrap::public('Failed to send email. Please verify the recipient address.'),
        Wrap::internal('SMTP server rejected message delivery'),
        Wrap::code('err:resend:email:smtp_failure'),
    );

    throw $fault;
}
```

### Aggregating multiple errors

Combine multiple throwables into a single fault envelope for batch operations:

```php
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\Wrap;

$errors = [$mysqlError, $redisError, $resendError];

$combined = Fault::combine(
    $errors,
    Wrap::public('Multiple service failures occurred. Please try again later.')
);

// The previous exception is an AggregateFault with the original errors
$prev = $combined->getPrevious();

// User‑facing message is taken from the newest layers first
Fault::publicMessages($combined);  // ['Multiple service failures occurred. Please try again later.']
```

### Merging context newest‑first

Merge context newest‑first. Newer layer values override older ones:

```php
$root = Fault::new(
    'Payment processing failed',
    Wrap::context(['env' => 'prod', 'customer_id' => 'cust_123'])
);
$wrap = Fault::wrap(
    $root,
    Wrap::context(['customer_id' => 'cust_456', 'payment_method' => 'card_789'])
);
$merged = Fault::context($wrap);

// ['customer_id' => 'cust_456', 'payment_method' => 'card_789', 'env' => 'prod']
```

### Capturing origins

Capture the file and line where a layer is added, then collect unique origins across the chain:

```php
use HosmelQ\Fault\Fault;
use HosmelQ\Fault\Wrap;

$base = Fault::new('Database connection failed');
$a = Fault::wrap($base, Wrap::origin());  // captures this call site
$b = Fault::wrap($a, Wrap::origin());

$origins = Fault::origins($b);
// [ ['file' => '/path/To/File.php', 'line' => 123], ... ]
```

### Using enums

Enums are accepted anywhere a message or code is expected. Backed enums use `value`, and unit enums use `name`:

```php
use HosmelQ\Fault\Wrap;

enum ErrorCode: int
{
    case NotFound = 404;
}

enum Notice: string
{
    case Failure = 'failure';
}

enum Level
{
    case Warning;
}

Wrap::code(ErrorCode::NotFound);

Wrap::internal(Notice::Failure);

Wrap::public(Level::Warning);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Hosmel Quintana](https://github.com/hosmelq)
- [All Contributors](../../contributors)

**Based on:**
- [fault](https://github.com/Southclaws/fault)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

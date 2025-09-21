<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use UnitEnum;

readonly class Wrap
{
    /**
     * Disallow instantiation; Wrap only provides static helpers.
     */
    private function __construct()
    {
    }

    /**
     * Attach a code identifier to the fault layer.
     *
     * @return callable(FaultException): void
     */
    public static function code(BackedEnum|int|string|UnitEnum $code): callable
    {
        /** @var int|string $code */
        $code = enum_value($code);

        return static function (FaultException $fault) use ($code): void {
            $fault->setCode($code);
        };
    }

    /**
     * Merge context from an external provider.
     *
     * @param callable(): (null|array<mixed>|iterable<mixed>)|iterable<mixed>|string $context
     *
     * @return callable(FaultException): void
     */
    public static function context(callable|iterable|string $context, mixed $value = null): callable
    {
        return static function (FaultException $fault) use ($context, $value): void {
            if (is_string($context)) {
                if ($context !== '') {
                    $fault->addContext([$context => $value]);
                }

                return;
            }

            $resolved = self::resolveIterable($context);

            if (is_null($resolved)) {
                return;
            }

            $fault->addContext($resolved);
        };
    }

    /**
     * Attach an internal message.
     *
     * @return callable(FaultException): void
     */
    public static function internal(BackedEnum|string|UnitEnum $message): callable
    {
        /** @var int|string $message */
        $message = enum_value($message);

        return static function (FaultException $fault) use ($message): void {
            $fault->addInternal((string) $message);
        };
    }

    /**
     * Capture the call site (file and line) where the wrapper is declared.
     *
     * The origin is captured at wrapper creation time and applied to the
     * FaultException when the wrapper runs.
     *
     * @return callable(FaultException): void
     */
    public static function origin(): callable
    {
        $origin = self::captureOrigin();

        return static function (FaultException $fault) use ($origin): void {
            if ($origin !== null) {
                $fault->setOrigin($origin);
            }
        };
    }

    /**
     * Attach a user-facing message.
     *
     * @return callable(FaultException): void
     */
    public static function public(BackedEnum|string|UnitEnum $message): callable
    {
        /** @var int|string $message */
        $message = enum_value($message);

        return static function (FaultException $fault) use ($message): void {
            $fault->addPublic((string) $message);
        };
    }

    /**
     * Capture the first non-library frame (file and line).
     *
     * @return null|array{file: string, line: int}
     */
    private static function captureOrigin(): null|array
    {
        $frames = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25), 2);

        foreach ($frames as $frame) {
            $class = $frame['class'] ?? null;
            $file = $frame['file'] ?? null;
            $line = $frame['line'] ?? null;

            if (! is_string($file)) {
                continue;
            }

            if ($file === '') {
                continue;
            }

            if (! is_int($line)) {
                continue;
            }

            if ($line <= 0) {
                continue;
            }

            if (is_string($class) && str_starts_with($class, __NAMESPACE__.'\\')) {
                continue;
            }

            return [
                'file' => $file,
                'line' => $line,
            ];
        }

        return null;
    }

    /**
     * Resolve a context provider to an iterable set of values.
     *
     * @param callable(): (null|iterable<mixed>)|iterable<mixed> $context
     *
     * @return null|iterable<mixed>
     */
    private static function resolveIterable(callable|iterable $context): null|iterable
    {
        if (is_iterable($context)) {
            return $context;
        }

        $resolved = $context();

        if (is_iterable($resolved)) {
            return $resolved;
        }

        return null;
    }
}

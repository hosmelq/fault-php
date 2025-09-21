<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use SplObjectStorage;
use Throwable;
use UnitEnum;

readonly class Fault
{
    /**
     * Disallow instantiation so Fault remains a static helper collection.
     */
    private function __construct()
    {
    }

    /**
     * Resolve the newest code across the chain.
     */
    public static function code(Throwable $throwable): null|int|string
    {
        $found = null;

        self::walk($throwable, static function (Throwable $current) use (&$found): void {
            if (! is_null($found)) {
                return;
            }

            if (! $current instanceof FaultException) {
                return;
            }

            if (is_null($code = $current->code())) {
                return;
            }

            $found = $code;
        });

        return $found;
    }

    /**
     * Combine multiple throwables into a single fault envelope.
     *
     * @param iterable<Throwable> $throwables
     * @param callable(FaultException): void ...$wrappers
     */
    public static function combine(iterable $throwables, callable ...$wrappers): FaultException
    {
        $list = [];

        foreach ($throwables as $throwable) {
            $list[] = $throwable;
        }

        $aggregate = new AggregateFault($list);
        $fault = new FaultException('', $aggregate);

        self::applyWrappers($fault, $wrappers); // @phpstan-ignore-line

        return $fault;
    }

    /**
     * Collect context across the chain. Newer layers override older values.
     *
     * @return array<string, mixed>
     */
    public static function context(Throwable $throwable): array
    {
        $context = [];

        self::walk($throwable, static function (Throwable $current) use (&$context): void {
            if (! $current instanceof FaultException) {
                return;
            }

            foreach ($current->context as $key => $value) {
                if (! array_key_exists($key, $context)) {
                    $context[$key] = $value;
                }
            }
        });

        return $context;
    }

    /**
     * Collect all internal messages across the chain.
     *
     * @return list<string>
     */
    public static function internals(Throwable $throwable): array
    {
        $internals = [];

        self::walk($throwable, static function (Throwable $current) use (&$internals): void {
            if ($current instanceof FaultException) {
                foreach ($current->internalMessages as $message) {
                    $internals[] = $message;
                }

                return;
            }

            $message = $current->getMessage();

            if ($message !== '') {
                $internals[] = $message;
            }
        });

        return $internals;
    }

    /**
     * Create a fresh fault with the provided internal message and optional wrappers.
     *
     * @param callable(FaultException): void ...$wrappers
     */
    public static function new(BackedEnum|string|UnitEnum $internalMessage, callable ...$wrappers): FaultException
    {
        /** @var int|string $internalMessage */
        $internalMessage = enum_value($internalMessage);

        $fault = new FaultException((string) $internalMessage);

        /** @var list<callable(FaultException): void> $wrappers */
        self::applyWrappers($fault, $wrappers);

        return $fault;
    }

    /**
     * Collect origin call sites across the chain.
     *
     * @return list<array{file: string, line: int}>
     */
    public static function origins(Throwable $throwable): array
    {
        $origins = [];

        self::walk($throwable, static function (Throwable $current) use (&$origins): void {
            if (! $current instanceof FaultException) {
                return;
            }

            if (is_null($origin = $current->origin)) {
                return;
            }

            $key = $origin['file'].':'.$origin['line'];

            if (array_key_exists($key, $origins)) {
                return;
            }

            $origins[$key] = $origin;
        });

        return array_values($origins);
    }

    /**
     * Collect all public-facing messages across the chain.
     *
     * @return list<string>
     */
    public static function publicMessages(Throwable $throwable): array
    {
        $messages = [];

        self::walk($throwable, static function (Throwable $current) use (&$messages): void {
            if (! $current instanceof FaultException) {
                return;
            }

            foreach ($current->publicMessages as $message) {
                $messages[] = $message;
            }
        });

        return $messages;
    }

    /**
     * Resolve the user-facing message across the chain.
     */
    public static function userMessage(Throwable $throwable): string
    {
        return implode(' ', self::publicMessages($throwable));
    }

    /**
     * Wrap an existing throwable with additional fault metadata.
     *
     * @param callable(FaultException): void ...$wrappers
     */
    public static function wrap(Throwable $throwable, callable ...$wrappers): FaultException
    {
        $fault = new FaultException('', $throwable);

        /** @var list<callable(FaultException): void> $wrappers */
        self::applyWrappers($fault, $wrappers);

        return $fault;
    }

    /**
     * Apply a set of wrappers to the given fault instance.
     *
     * @param list<callable(FaultException): void> $wrappers
     */
    private static function applyWrappers(FaultException $fault, array $wrappers): void
    {
        foreach ($wrappers as $wrapper) {
            $wrapper($fault);
        }
    }

    /**
     * Walk the throwable tree depth-first, visiting newest errors first.
     *
     * @param callable(Throwable): void $visitor
     */
    private static function walk(Throwable $throwable, callable $visitor): void
    {
        $stack = [$throwable];
        $seen = new SplObjectStorage();

        while ($stack !== []) {
            /** @var Throwable $current */
            $current = array_pop($stack);

            if (isset($seen[$current])) {
                continue;
            }

            $seen[$current] = true;

            $visitor($current);

            if ($current instanceof ProvidesThrowables) {
                foreach ($current->throwables() as $child) {
                    $stack[] = $child;
                }
            }

            $previous = $current->getPrevious();

            if ($previous instanceof Throwable) {
                array_unshift($stack, $previous);
            }
        }
    }
}

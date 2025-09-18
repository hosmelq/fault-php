<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use BackedEnum;
use Throwable;
use UnitEnum;

final class Fault
{
    /**
     * Prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Create a wrapper that assigns a URN-style error code.
     */
    public static function code(BackedEnum|Code|string|UnitEnum $code): callable
    {
        $value = $code instanceof Code ? $code->urn() : enum_value($code);

        return static function (FaultException $fault) use ($value): void {
            $fault->setCode($value);
        };
    }

    /**
     * Locate the first fault code within the throwable chain.
     */
    public static function codeFor(Throwable $throwable): null|string
    {
        $current = $throwable;

        while ($current instanceof Throwable) {
            if ($current instanceof FaultException) {
                $code = $current->code();

                if ($code !== null) {
                    return $code;
                }
            }

            $current = $current->getPrevious();
        }

        return null;
    }

    /**
     * Create a wrapper that appends a new internal message.
     */
    public static function internal(BackedEnum|string|UnitEnum $message): callable
    {
        $value = enum_value($message);

        return static function (FaultException $fault) use ($value): void {
            $fault->addInternal($value);
        };
    }

    /**
     * Create a fresh fault with the provided internal message and optional wrappers.
     *
     * @param callable(FaultException): void ...$wrappers
     */
    public static function new(BackedEnum|string|UnitEnum $internalMessage, callable ...$wrappers): FaultException
    {
        $fault = new FaultException(enum_value($internalMessage));

        /** @var list<callable(FaultException): void> $wrappers */
        self::applyWrappers($fault, $wrappers);

        return $fault;
    }

    /**
     * Create a wrapper that appends a user-facing message.
     */
    public static function public(BackedEnum|string|UnitEnum $message): callable
    {
        $value = enum_value($message);

        return static function (FaultException $fault) use ($value): void {
            $fault->addPublic($value);
        };
    }

    /**
     * Resolve the aggregated public message for a throwable chain.
     */
    public static function userFacingMessage(Throwable $throwable): string
    {
        $current = $throwable;

        while ($current instanceof Throwable) {
            if ($current instanceof FaultException) {
                $message = $current->userFacingMessage();

                if ($message !== '') {
                    return $message;
                }
            }

            $current = $current->getPrevious();
        }

        return '';
    }

    /**
     * Wrap an existing throwable with additional fault metadata.
     *
     * @param callable(FaultException): void ...$wrappers
     */
    public static function wrap(null|Throwable $throwable, callable ...$wrappers): null|FaultException
    {
        if (! $throwable instanceof Throwable) {
            return null;
        }

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
}

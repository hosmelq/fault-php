<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use ArrayIterator;
use IteratorAggregate;
use RuntimeException;
use Throwable;
use Traversable;

/**
 * @implements IteratorAggregate<int, Throwable>
 */
class AggregateFault extends RuntimeException implements IteratorAggregate, ProvidesThrowables
{
    /**
     * Instantiate the aggregate fault with the provided throwables.
     *
     * @param list<Throwable> $throwables
     */
    public function __construct(private readonly array $throwables)
    {
        parent::__construct('');
    }

    /**
     * Expose an iterator over every aggregated throwable.
     *
     * @return Traversable<int, Throwable>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->throwables);
    }

    /**
     * Expose the aggregated throwables for this fault envelope.
     *
     * @return iterable<Throwable>
     */
    public function throwables(): iterable
    {
        return $this->throwables;
    }
}

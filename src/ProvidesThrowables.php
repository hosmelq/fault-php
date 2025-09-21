<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use Throwable;

interface ProvidesThrowables extends Throwable
{
    /**
     * Expose the nested throwables managed by this provider.
     *
     * @return iterable<Throwable>
     */
    public function throwables(): iterable;
}

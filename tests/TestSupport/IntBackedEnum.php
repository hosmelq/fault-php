<?php

declare(strict_types=1);

namespace HosmelQ\Fault\Tests\TestSupport;

enum IntBackedEnum: int
{
    case NotFound = 404;
}

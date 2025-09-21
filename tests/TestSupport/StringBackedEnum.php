<?php

declare(strict_types=1);

namespace HosmelQ\Fault\Tests\TestSupport;

enum StringBackedEnum: string
{
    case Failure = 'failure';
}

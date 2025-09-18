<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

arch('annotations')
    ->expect('HosmelQ\Fault')
    ->toHaveMethodsDocumented()
    ->toHavePropertiesDocumented();

arch('strict types')
    ->expect('HosmelQ\Fault')
    ->toUseStrictTypes();

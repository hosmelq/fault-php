<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

arch('annotations')
    ->expect('HosmelQ\Namespace')
    ->toHaveMethodsDocumented()
    ->toHavePropertiesDocumented();

arch('strict types')
    ->expect('HosmelQ\Namespace')
    ->toUseStrictTypes();

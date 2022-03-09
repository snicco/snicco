<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [
        BetterWPHooksBundle::class
    ]
];

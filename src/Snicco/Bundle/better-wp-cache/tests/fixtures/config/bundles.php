<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [
        BetterWPCacheBundle::class
    ]
];

<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [BetterWPCacheBundle::class],
    ],
];

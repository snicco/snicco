<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Debug\DebugBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [BetterWPHooksBundle::class, HttpRoutingBundle::class, DebugBundle::class],
    ],
];

<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [BetterWPDBBundle::class],
    ],
];

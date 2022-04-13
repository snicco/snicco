<?php

declare(strict_types=1);

use Snicco\Bundle\Blade\BladeBundle;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [TemplatingBundle::class, BladeBundle::class],
    ],
];

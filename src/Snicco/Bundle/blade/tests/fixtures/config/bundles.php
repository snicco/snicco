<?php

declare(strict_types=1);

use Snicco\Bundle\Blade\BladeBundle;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [
        TemplatingBundle::class,
        BladeBundle::class,
    ],
];

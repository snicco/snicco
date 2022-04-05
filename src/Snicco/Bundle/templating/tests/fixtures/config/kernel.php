<?php

declare(strict_types=1);

use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    'bundles' => [
        Environment::ALL => [TemplatingBundle::class],
    ],
];

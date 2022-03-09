<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Session\SessionBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [
        SessionBundle::class,
        BetterWPDBBundle::class,
        BetterWPHooksBundle::class,
    ],
];

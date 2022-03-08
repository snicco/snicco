<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [
        BetterWPHooksBundle::class,
        BetterWPMailBundle::class,
        HttpRoutingBundle::class
    ]
];
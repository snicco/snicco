<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    Environment::ALL => [BetterWPMailBundle::class],
];

<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [

    Environment::ALL => [
        HttpRoutingBundle::class
    ]

];
<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

return [
    MiddlewareOption::GROUPS => [
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [
            // empty
        ],
    ],
];

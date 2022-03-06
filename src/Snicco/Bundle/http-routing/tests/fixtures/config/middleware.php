<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;

return [
    MiddlewareOption::GROUPS => [
        Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator::GLOBAL_MIDDLEWARE => [
            // empty
        ]
    ]
];
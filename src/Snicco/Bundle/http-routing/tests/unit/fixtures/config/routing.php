<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\RoutingOption;

return [

    RoutingOption::HOST => 'snicco.test',
    RoutingOption::ROUTE_DIRECTORIES => [
        dirname(__DIR__) . '/routes'
    ]

];
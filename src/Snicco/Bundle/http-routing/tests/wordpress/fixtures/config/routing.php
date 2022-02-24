<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\RoutingOption;

return [
    RoutingOption::HOST => 'foo.com',
    RoutingOption::ROUTE_DIRECTORIES => [dirname(__DIR__) . '/routes'],
    RoutingOption::API_ROUTE_DIRECTORIES => [dirname(__DIR__) . '/api-routes'],
    RoutingOption::API_PREFIX => '/sniccowp'
];
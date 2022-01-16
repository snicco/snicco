<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Tests\Routing\RouteLoaderTest;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $router) {
    $router->page(
        'admin_route_1',
        RouteLoaderTest::ADMIN_PATH,
        RoutingTestController::class,
        [],
    );
};

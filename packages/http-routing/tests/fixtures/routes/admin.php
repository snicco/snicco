<?php

declare(strict_types=1);

use Tests\HttpRouting\unit\Routing\RouteLoaderTest;
use Tests\HttpRouting\fixtures\Controller\RoutingTestController;
use Snicco\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $router) {
    $router->page(
        'admin_route_1',
        RouteLoaderTest::ADMIN_PATH,
        RoutingTestController::class,
        [],
    );
};


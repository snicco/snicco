<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\Routing\RouteLoader\PHPFileRouteLoaderTest;

return function (AdminRoutingConfigurator $router): void {
    $router->page('admin_route_1', PHPFileRouteLoaderTest::ADMIN_PATH, RoutingTestController::class, [],);
};

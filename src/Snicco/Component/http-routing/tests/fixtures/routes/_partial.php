<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\Routing\RouteLoader\PHPFileRouteLoaderTest;

return function (WebRoutingConfigurator $router): void {
    $router->get(
        'partial1',
        PHPFileRouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};

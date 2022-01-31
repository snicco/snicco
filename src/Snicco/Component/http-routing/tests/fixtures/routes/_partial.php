<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\Routing\RouteLoaderTest;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'partial1',
        RouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};
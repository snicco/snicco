<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Tests\Routing\RouteLoaderTest;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'partial1',
        RouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};
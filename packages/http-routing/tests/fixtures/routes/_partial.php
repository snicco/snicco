<?php

declare(strict_types=1);

use Tests\HttpRouting\unit\Routing\RouteLoaderTest;
use Tests\HttpRouting\fixtures\Controller\RoutingTestController;
use Snicco\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'partial1',
        RouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};
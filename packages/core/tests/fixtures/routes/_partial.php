<?php

declare(strict_types=1);

use Tests\Core\unit\Routing\RouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'partial1',
        RouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};
<?php

declare(strict_types=1);

use Tests\Core\unit\Routing\PHPFileRouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'partial1',
        PHPFileRouteLoaderTest::PARTIAL_PATH,
        RoutingTestController::class
    )->middleware('partial');
};
<?php

declare(strict_types=1);

use Tests\Core\unit\Routing\PHPFileRouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $router) {
    $router->page(
        'admin_route_1',
        PHPFileRouteLoaderTest::ADMIN_PATH,
        RoutingTestController::class,
        [],
    );
};


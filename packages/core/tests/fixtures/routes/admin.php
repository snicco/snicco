<?php

declare(strict_types=1);

use Snicco\Core\Routing\AdminRoutingConfigurator;
use Tests\Core\unit\Routing\PHPFileRouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

return function (AdminRoutingConfigurator $router) {
    $router->admin(
        'admin_route_1',
        PHPFileRouteLoaderTest::ADMIN_PATH,
        RoutingTestController::class
    );
};
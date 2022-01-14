<?php

declare(strict_types=1);

use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'cart',
        '/cart',
        RoutingTestController::class
    );
};
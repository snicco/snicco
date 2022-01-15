<?php

declare(strict_types=1);

use Tests\HttpRouting\fixtures\Controller\RoutingTestController;
use Snicco\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'posts',
        '/posts',
        RoutingTestController::class
    );
};
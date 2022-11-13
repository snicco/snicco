<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

return function (WebRoutingConfigurator $router): void {
    $router->get('component_one.first_route', '/component-1', RoutingTestController::class);
};

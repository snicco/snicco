<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

return function (WebRoutingConfigurator $router): void {
    // nope. not allowed here cause in api dir.
    $router->get('bad', '/bad', RoutingTestController::class);
};

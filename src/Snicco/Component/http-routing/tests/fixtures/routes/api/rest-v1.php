<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

return function (WebRoutingConfigurator $router): void {
    $router->get('posts', '/posts', RoutingTestController::class);
};

<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\wordpress\fixtures\Controller\HttpRunnerTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get('register', '/register', HttpRunnerTestController::class);
};
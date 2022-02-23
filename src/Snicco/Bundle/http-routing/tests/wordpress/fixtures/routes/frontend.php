<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\wordpress\fixtures\Controller\HttpRunnerTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get('frontend1', '/frontend1', HttpRunnerTestController::class);
    $router->get('no-response', '/no-response', [HttpRunnerTestController::class, 'noResponse']);
    $router->get('stream', '/stream', [HttpRunnerTestController::class, 'stream']);
};
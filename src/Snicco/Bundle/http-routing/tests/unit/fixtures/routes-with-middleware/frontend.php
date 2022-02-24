<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\Middleware\MiddlewareOne;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\Middleware\MiddlewareTwo;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\RoutingBundleTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get('web1', '/web1', RoutingBundleTestController::class)->middleware(MiddlewareOne::class);
    $router->get('web2', '/web2', RoutingBundleTestController::class)->middleware(
        [MiddlewareOne::class, MiddlewareTwo::class]
    );
};

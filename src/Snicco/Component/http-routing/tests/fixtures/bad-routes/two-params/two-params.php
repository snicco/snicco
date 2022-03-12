<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

return function (RoutingConfigurator $router, $foo): void {
    // nope. Only one param received.
};

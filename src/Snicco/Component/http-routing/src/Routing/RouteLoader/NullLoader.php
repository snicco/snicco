<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

final class NullLoader implements RouteLoader
{
    public function loadWebRoutes(WebRoutingConfigurator $configurator): void
    {
        // TODO: Implement loadWebRoutes() method.
    }

    public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
    {
        // TODO: Implement loadAdminRoutes() method.
    }
}
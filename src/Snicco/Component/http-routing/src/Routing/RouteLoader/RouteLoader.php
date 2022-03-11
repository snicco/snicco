<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

interface RouteLoader
{
    public function loadWebRoutes(WebRoutingConfigurator $configurator): void;

    public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void;
}

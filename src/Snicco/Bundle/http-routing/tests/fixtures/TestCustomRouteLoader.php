<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\fixtures;

use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
 * @internal
 *
 * @psalm-internal Snicco\Bundle\HttpRouting\Tests
 */
final class TestCustomRouteLoader implements RouteLoader
{
    public function loadWebRoutes(WebRoutingConfigurator $configurator): void
    {
        $configurator->get('foo', '/foo-custom', RoutingBundleTestController::class);
    }

    public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
    {
        // Nothing
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\fixtures;

use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

/**
 * @internal
 *
 * @psalm-internal Snicco\Bundle\HttpRouting\Tests
 */
final class TestCustomRouteLoadingOptions implements RouteLoadingOptions
{
    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
        return [];
    }

    public function getRouteAttributes(string $file_basename): array
    {
        return [
            RoutingConfigurator::PREFIX_KEY => '/custom-prefix',
        ];
    }
}

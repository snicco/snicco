<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Routing\RouteCollection;
use Snicco\Contracts\RouteUrlGenerator;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\Routing\FastRoute\FastRouteUrlMatcher;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;

/**
 * @internal
 */
trait CreateRouteCollection
{
    
    protected function createRouteCollection(string $cache_file = null) :RouteCollectionInterface
    {
        return new RouteCollection(new FastRouteUrlMatcher(), $cache_file);
    }
    
    protected function createCachedRouteCollection($cache_file) :void
    {
        $this->routes = $this->createRouteCollection($cache_file);
        $this->container->instance(RouteCollectionInterface::class, $this->routes);
        $this->container->instance(
            RouteUrlGenerator::class,
            new FastRouteUrlGenerator($this->routes)
        );
    }
    
}
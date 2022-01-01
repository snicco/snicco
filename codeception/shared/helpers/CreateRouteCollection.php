<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Routing\RouteCollection;
use Snicco\Core\Contracts\UrlGeneratorInterface;
use Snicco\Core\Contracts\RouteCollectionInterface;
use Snicco\Core\Routing\FastRoute\RouteUrlGenerator;
use Snicco\Core\Routing\FastRoute\FastRouteUrlMatcher;

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
        unset($this->container[UrlGeneratorInterface::class]);
        $this->container->instance(
            UrlGeneratorInterface::class,
            $this->newUrlGenerator()
        );
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Routing\FastRoute;

use Snicco\Routing\Route;
use Snicco\Routing\RoutingResult;
use Snicco\Contracts\RouteMatcher;
use Snicco\Traits\PreparesRouteForExport;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;

class CachedFastRouteMatcher implements RouteMatcher
{
    
    use HydratesFastRoutes;
    use TransformFastRoutes;
    use PreparesRouteForExport;
    
    private FastRouteMatcher $uncached_matcher;
    private array            $route_cache;
    private string           $route_cache_file;
    
    public function __construct(FastRouteMatcher $uncached_matcher, string $route_cache_file)
    {
        
        $this->uncached_matcher = $uncached_matcher;
        $this->uncached_matcher->setRouteStoragePreparation(function (Route $route) {
            
            return $this->serializeRoute($route);
            
        });
        $this->route_cache_file = $route_cache_file;
        
        if (file_exists($route_cache_file)) {
            
            $this->route_cache = require $route_cache_file;
            
        }
        
    }
    
    public function add(Route $route, $methods)
    {
        $this->uncached_matcher->add($route, $methods);
    }
    
    public function find(string $method, string $path) :RoutingResult
    {
        
        if (isset($this->route_cache)) {
            
            $dispatcher = new RouteDispatcher($this->route_cache);
            
            return $this->hydrateRoutingResult(
                $this->toRoutingResult($dispatcher->dispatch($method, $path))
            );
            
        }
        
        $routing_result = $this->uncached_matcher->find($method, $path);
        
        return $this->hydrateRoutingResult($routing_result);
        
    }
    
    public function createCache()
    {
        file_put_contents(
            $this->route_cache_file,
            '<?php
declare(strict_types=1); return '.var_export($this->uncached_matcher->getRouteMap(), true).';'
        );
    }
    
    private function serializeRoute(Route $route) :array
    {
        return $this->prepareForVarExport($route->asArray());
        
    }
    
}
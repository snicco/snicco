<?php

declare(strict_types=1);

namespace Snicco\Routing\FastRoute;

use Snicco\Routing\Route;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Snicco\Routing\RoutingResult;
use Snicco\Contracts\RouteUrlMatcher;
use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;

class FastRouteUrlMatcher implements RouteUrlMatcher
{
    
    private RouteCollector  $collector;
    private FastRouteSyntax $route_regex;
    private RouteDispatcher $cached_dispatcher;
    
    public function __construct()
    {
        $this->collector = new RouteCollector(new RouteParser(), new DataGenerator());
        $this->route_regex = new FastRouteSyntax();
    }
    
    public function add(Route $route, array $methods)
    {
        $this->collector->addRoute(
            $methods,
            $this->convertUrl($route),
            $route->asArray()
        );
    }
    
    public function find(string $method, string $path) :RoutingResult
    {
        $dispatcher = $this->cached_dispatcher ?? new RouteDispatcher($this->collector->getData());
        
        $route_info = $dispatcher->dispatch($method, $path);
        
        return $this->toRoutingResult($route_info);
    }
    
    public function loadDataFromCache($cache_data)
    {
        $this->cached_dispatcher = new RouteDispatcher($cache_data);
    }
    
    public function getCacheableData() :array
    {
        return $this->collector->getData();
    }
    
    public function isCacheable() :bool
    {
        return true;
    }
    
    private function convertUrl(Route $route) :string
    {
        return $this->route_regex->convert($route);
    }
    
    private function toRoutingResult(array $routing_info) :RoutingResult
    {
        if ($routing_info[0] !== Dispatcher::FOUND) {
            return new RoutingResult(null, []);
        }
        
        $route = Route::hydrate($routing_info[1]);
        
        return new RoutingResult(
            $route,
            $this->normalize($routing_info[2])
        );
    }
    
    private function normalize(array $captured_url_segments) :array
    {
        return array_map(function ($value) {
            return rtrim($value, '/');
        }, $captured_url_segments);
    }
    
}
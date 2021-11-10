<?php

declare(strict_types=1);

namespace Snicco\Routing\FastRoute;

use Snicco\Routing\Route;
use FastRoute\RouteCollector;
use Snicco\Routing\RoutingResult;
use Snicco\Contracts\RouteMatcher;
use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;

class FastRouteMatcher implements RouteMatcher
{
    
    use TransformFastRoutes;
    
    private RouteCollector  $collector;
    private FastRouteSyntax $route_regex;
    
    /**
     * @var callable|null
     */
    private $route_storage_preparation;
    
    public function __construct()
    {
        
        $this->collector = new RouteCollector(new RouteParser(), new DataGenerator());
        $this->route_regex = new FastRouteSyntax();
        
    }
    
    public function add(Route $route, array $methods)
    {
        
        $url = $this->convertUrl($route);
        
        $this->collector->addRoute($methods, $url, $this->prepareForStorage($route));
        
    }
    
    public function find(string $method, string $path) :RoutingResult
    {
        $dispatcher = new RouteDispatcher($this->collector->getData());
        
        $route_info = $dispatcher->dispatch($method, $path);
        
        return $this->toRoutingResult($route_info);
    }
    
    public function getRouteMap() :array
    {
        return $this->collector->getData() ?? [];
    }
    
    public function setRouteStoragePreparation(callable $callable)
    {
        $this->route_storage_preparation = $callable;
    }
    
    private function convertUrl(Route $route) :string
    {
        return $this->route_regex->convert($route);
    }
    
    private function prepareForStorage(Route $route)
    {
        
        if ( ! is_callable($this->route_storage_preparation)) {
            return $route;
        }
        
        return call_user_func($this->route_storage_preparation, $route);
        
    }
    
}
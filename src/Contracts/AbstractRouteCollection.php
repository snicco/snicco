<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Support\Arr;
use Snicco\Routing\Route;
use Snicco\Http\Psr7\Request;
use Snicco\Routing\RoutingResult;
use Snicco\Traits\ValidatesRoutes;
use Snicco\Factories\ConditionFactory;
use Snicco\Factories\RouteActionFactory;

abstract class AbstractRouteCollection
{
    
    use ValidatesRoutes;
    
    protected ConditionFactory   $condition_factory;
    protected RouteActionFactory $action_factory;
    protected array              $routes                      = [];
    protected array              $name_list                   = [];
    private ?RoutingResult       $query_filter_routing_result = null;
    
    abstract public function add(Route $route) :Route;
    
    abstract public function findByName(string $name) :?Route;
    
    abstract public function withWildCardUrl(string $method) :array;
    
    abstract public function loadIntoDispatcher(bool $global_routes) :void;
    
    public function matchForQueryFiltering(Request $request) :RoutingResult
    {
        
        $result = $this->match($request);
        
        $this->query_filter_routing_result = $result;
        
        return $result;
        
    }
    
    public function match(Request $request) :RoutingResult
    {
        
        if ($this->query_filter_routing_result) {
            
            return $this->query_filter_routing_result;
            
        }
        
        $result = $this->route_matcher->find(
            $request->getMethod(),
            $request->routingPath()
        );
        
        if ( ! $route = $result->route()) {
            
            return new RoutingResult(null);
            
        }
        
        $route = $this->giveFactories($route)->instantiateConditions();
        
        if ( ! $route->satisfiedBy($request)) {
            
            return new RoutingResult(null);
            
        }
        
        $route->instantiateAction();
        
        return $result;
        
    }
    
    protected function giveFactories(Route $route) :Route
    {
        
        $route->setActionFactory($this->action_factory);
        $route->setConditionFactory($this->condition_factory);
        
        return $route;
    }
    
    protected function addToCollection(Route $route)
    {
        
        foreach ($route->getMethods() as $method) {
            
            $this->routes[$method][] = $route;
            
        }
        
    }
    
    /**
     * @param  string  $name
     *
     * @return array|Route
     */
    protected function findInLookUps(string $name)
    {
        
        return $this->name_list[$name] ?? null;
        
    }
    
    protected function findByRouteName(string $name) :?Route
    {
        
        return collect($this->routes)
            ->flatten()
            ->first(fn(Route $route) => $route->getName() === $name);
        
    }
    
    protected function findWildcardsInCollection(string $method) :array
    {
        
        return collect($this->routes[$method] ?? [])
            ->filter(fn(Route $route) => trim($route->getUrl(), '/') === ROUTE::ROUTE_WILDCARD)
            ->all();
        
    }
    
    protected function prepareOutgoingRoute($routes) :void
    {
        
        $routes = Arr::wrap($routes);
        
        collect($routes)->each(fn(Route $route) => $this->giveFactories($route));
        
    }
    
}
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
    protected RouteMatcher       $route_matcher;
    protected array              $routes                      = [];
    protected array              $name_list                   = [];
    private ?RoutingResult       $query_filter_routing_result = null;
    
    public function __construct(
        RouteMatcher $route_matcher,
        ConditionFactory $condition_factory,
        RouteActionFactory $action_factory
    ) {
        
        $this->route_matcher = $route_matcher;
        $this->condition_factory = $condition_factory;
        $this->action_factory = $action_factory;
        
    }
    
    /**
     * Add a route to the collection.
     *
     * @param  Route  $route
     *
     * @return Route
     */
    abstract public function add(Route $route) :Route;
    
    /**
     * Find a named route in the collection.
     *
     * @param  string  $name
     *
     * @return Route|null
     */
    abstract public function findByName(string $name) :?Route;
    
    /**
     * Find all routes that don't have an url but a custom condition.
     *
     * @param  string  $method
     *
     * @return Route[]
     */
    abstract public function withWildCardUrl(string $method) :array;
    
    /**
     * Load all added routes into the route dispatcher.
     * The dispatcher will later perform the actual matching of all routes
     * against a given request.
     *
     * @param  bool  $global_routes
     */
    abstract public function loadIntoDispatcher(bool $global_routes) :void;
    
    /**
     * For frontend requests we have to check on the "do_parse_request" hook
     * if the developer wants to filter the current WP_QUERY instance dynamically.
     * If we do find a result we store it as a property so that we don't have to match against the
     * request later on the template_redirect hook.
     *
     * @param  Request  $request
     *
     * @return RoutingResult
     */
    public function matchForQueryFiltering(Request $request) :RoutingResult
    {
        
        $result = $this->match($request);
        
        $this->query_filter_routing_result = $result;
        
        return $result;
        
    }
    
    /**
     * Match the current request against the registered routes.
     * We can't evaluate routes based on custom conditions here because this method might be
     * called before WordPress has even parsed the main query. So all conditions based on
     * conditional tags would possibly be a false negative.
     *
     * @param  Request  $request
     *
     * @return RoutingResult
     */
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
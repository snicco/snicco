<?php

declare(strict_types=1);

namespace Snicco\Routing;

use RuntimeException;
use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Http\Psr7\Request;
use Snicco\Traits\ValidatesRoutes;
use Snicco\Contracts\RouteUrlMatcher;
use Snicco\Factories\RouteConditionFactory;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\Routing\FastRoute\FastRouteUrlMatcher;

class RouteCollection implements RouteCollectionInterface
{
    
    use ValidatesRoutes;
    
    private Route $current_route;
    
    /**
     * All url-routable routes keyed by method
     *
     * @var array<string,Route[]>|array<string,array>
     */
    private array $url_routes = [];
    
    /**
     * All routes with conditions keyed by method
     *
     * @var Route[]|array<array>
     */
    private array $condition_routes = [];
    
    /**
     * A list of all named routes
     *
     * @var Route[]|array<array>
     */
    private array $name_list = [];
    
    /**
     * All routes that have been loaded into the url matcher. Used to tack duplicates.
     *
     * @var array
     */
    private array $loaded_into_dispatcher = [];
    
    private RouteUrlMatcher $url_matcher;
    private RouteCache      $route_cache;
    
    public function __construct(?RouteUrlMatcher $url_matcher = null, ?string $cache_file = null)
    {
        $this->url_matcher = $url_matcher ?? new FastRouteUrlMatcher();
        
        if ( ! $cache_file) {
            return;
        }
        
        $this->route_cache = new RouteCache($cache_file);
        
        if ($this->route_cache->created()) {
            $this->url_routes = $this->route_cache->urlRoutes();
            $this->condition_routes = $this->route_cache->conditionRoutes();
            $this->name_list = $this->route_cache->nameList();
            $this->url_matcher->loadDataFromCache($this->route_cache->routeUrlData());
        }
    }
    
    public function add(Route $route) :Route
    {
        if (isset($this->route_cache) && $this->route_cache->created()) {
            throw new RuntimeException("Routes cant be added if the cache was already created.");
        }
        
        foreach ($route->getMethods() as $method) {
            if ($route->routableByUrl()) {
                $this->url_routes[$method][] = $route;
            }
            else {
                $this->condition_routes[$method][] = $route;
            }
        }
        
        return $route;
    }
    
    public function findByName(string $name) :?Route
    {
        $route = $this->name_list[$name] ?? null;
        
        if (is_array($route)) {
            $route = Route::hydrate($route);
        }
        
        return $route;
    }
    
    public function addToUrlMatcher() :void
    {
        if (isset($this->route_cache) && $this->route_cache->created()) {
            return;
        }
        
        $this->reindexRouteNames();
        
        foreach ($this->url_routes as $method => $routes) {
            /** @var Route $route */
            foreach ($routes as $route) {
                if ($this->wasAlreadyAdded($route, $method)) {
                    continue;
                }
                
                $this->validateAttributes($route);
                
                $this->url_matcher->add($route, [$method]);
                
                $this->loaded_into_dispatcher[$method][] = $route->getUrl();
            }
        }
        
        foreach ($this->condition_routes as $method => $routes) {
            foreach ($routes as $route) {
                $this->validateAttributes($route);
            }
        }
        
        if (isset($this->route_cache)
            && ! $this->route_cache->created()
            && $this->url_matcher->isCacheable()) {
            $this->route_cache->create(
                $this->url_routes,
                $this->condition_routes,
                $this->name_list,
                $this->url_matcher->getCacheableData()
            );
        }
    }
    
    public function matchByUrlPattern(Request $request) :?Route
    {
        if (isset($this->current_route)) {
            return $this->current_route;
        }
        
        $result = $this->url_matcher->find(
            $request->getMethod(),
            $request->routingPath()
        );
        
        if ( ! $result->hasRoute()) {
            return null;
        }
        
        $route = $result->route();
        
        if ($route->needsTrailingSlash() && Str::doesNotEndWith($request->path(), '/')) {
            return null;
        }
        
        $route->setCapturedParameters($result->capturedUrlSegmentValues());
        
        return $route;
    }
    
    public function matchByConditions(Request $request, RouteConditionFactory $factory) :?Route
    {
        if (isset($this->current_route)) {
            return $this->current_route;
        }
        
        $possible_routes = Arr::get($this->condition_routes, $request->getMethod(), []);
        
        if ( ! count($possible_routes)) {
            return null;
        }
        
        $route = null;
        
        foreach ($possible_routes as $possible_route) {
            $possible_route = $possible_route instanceof Route
                ? $possible_route
                : Route::hydrate($possible_route);
            
            if ($possible_route->instantiateConditions($factory)->satisfiedBy($request)) {
                $route = $possible_route;
                break;
            }
        }
        
        if ( ! $route) {
            return null;
        }
        
        return $route;
    }
    
    public function setCurrentRoute(Route $route) :void
    {
        $this->current_route = $route;
    }
    
    /**
     * We reindex all routes by name because it's possible that a developer
     * added the name attribute to a route after it has been added to the RouteCollection.
     * By looping over all routes once we avoid having to loop over all routes every time a
     * developer needs to build a route url.
     */
    private function reindexRouteNames() :void
    {
        $this->reindexRoutes($this->url_routes);
        $this->reindexRoutes($this->condition_routes);
    }
    
    private function reindexRoutes(array $routes)
    {
        $routes = array_filter(Arr::flatten($routes), function (Route $route) {
            return ! empty(
                $route->getName()
                && ! isset($this->name_list[$route->getName()])
            );
        });
        
        array_walk($routes, function (Route $route) {
            if (isset($this->name_list[$name = $route->getName()])) {
                return;
            }
            
            $this->name_list[$name] = $route;
        });
    }
    
    /**
     * We don't load a Route twice. This can happen if a users includes attempts to override an
     * inbuilt route. In this case the first route takes priority which is almost always the
     * user-defined route.
     *
     * @param  Route  $route
     * @param  string  $method
     *
     * @return bool
     */
    private function wasAlreadyAdded(Route $route, string $method) :bool
    {
        if ( ! isset($this->loaded_into_dispatcher[$method])) {
            return false;
        }
        
        return in_array($route->getUrl(), $this->loaded_into_dispatcher[$method]);
    }
    
}
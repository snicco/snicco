<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Arr;
use Snicco\Traits\DeserializesRoutes;
use Snicco\Factories\ConditionFactory;
use Snicco\Factories\RouteActionFactory;
use Snicco\Traits\PreparesRouteForExport;
use Snicco\Contracts\AbstractRouteCollection;
use Snicco\Routing\FastRoute\CachedFastRouteMatcher;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

/**
 * @property CachedFastRouteMatcher $route_matcher
 */
class CachedFastRouteCollection extends AbstractRouteCollection
{
    
    use PreparesRouteForExport;
    use DeserializesRoutes;
    
    protected array $name_list     = [];
    private string  $cache_file;
    private array   $cached_routes = [];
    
    public function __construct(
        CachedFastRouteMatcher $route_matcher,
        ConditionFactory $condition_factory,
        RouteActionFactory $action_factory,
        string $cache_file
    ) {
        
        parent::__construct($route_matcher, $condition_factory, $action_factory);
        
        $this->cache_file = $cache_file;
        
        if (file_exists($cache_file)) {
            
            $cache = require $cache_file;
            $this->name_list = $cache['lookups'];
            $this->cached_routes = $cache['routes'];
            
        }
        
    }
    
    public function add(Route $route) :Route
    {
        
        $this->addToCollection($route);
        
        return $route;
        
    }
    
    public function loadIntoDispatcher(bool $global_routes) :void
    {
        
        if (file_exists($this->cache_file)) {
            return;
        }
        
        $this->loadOnce();
        $this->cacheRouteCollection();
        $this->cacheFastRouteMap();
        
    }
    
    public function findByName(string $name) :?Route
    {
        
        $route = $this->findInLookUps($name);
        
        if ($route) {
            
            $route = Route::hydrate($route);
            
        }
        
        if ( ! $route) {
            
            $route = $this->findByRouteName($name);
            
        }
        
        if ( ! $route) {
            
            return null;
            
        }
        
        $this->prepareOutgoingRoute($route);
        
        return $route;
        
    }
    
    public function withWildCardUrl(string $method) :array
    {
        
        $routes = $this->findCachedWildcardRoutes($method);
        
        if ( ! count($routes)) {
            
            $routes = $this->findWildcardsInCollection($method);
            
        }
        
        return collect($routes)->each(function (Route $route) {
            
            $this->prepareOutgoingRoute($route);
            
        })->all();
        
    }
    
    protected function prepareOutgoingRoute($routes) :void
    {
        
        $routes = Arr::wrap($routes);
        
        $routes = collect($routes)->each(function (Route $route) {
            
            $this->unserializeAction($route);
            $this->unserializeWpQueryFilter($route);
            
        })->all();
        
        parent::prepareOutgoingRoute($routes);
        
    }
    
    /**
     * @throws ConfigurationException
     */
    private function loadOnce()
    {
        foreach ($this->routes as $method => $routes) {
            
            /** @var Route $route */
            foreach ($routes as $route) {
                
                $this->validateAttributes($route);
                
                $this->route_matcher->add($route, [$method]);
                
            }
            
        }
    }
    
    private function findCachedWildcardRoutes(string $method) :array
    {
        
        $routes = collect($this->cached_routes[$method] ?? [])
            ->filter(fn(array $route) => trim($route['url'], '/') === ROUTE::ROUTE_WILDCARD)
            ->map(fn(array $route) => Route::hydrate($route));
        
        return $routes->all();
        
    }
    
    /**
     * Cache all added routes as a var_export.
     * We need to maintain a separate cache file because the used route matcher is not guaranteed
     * to have the same internal storage format for added routes.
     */
    private function cacheRouteCollection()
    {
        $lookups = collect($this->routes)
            ->flatten()
            ->filter(fn(Route $route) => $route->getName() !== null && $route->getName() !== '')
            ->flatMap(function (Route $route) {
                
                return [
                    $route->getName() => $this->prepareForVarExport($route->asArray()),
                ];
                
            })
            ->all();
        
        $array_routes = [];
        
        foreach ($this->routes as $method => $routes) {
            
            /** @var Route $route */
            foreach ($routes as $route) {
                
                $array_routes[$method][] = $this->prepareForVarExport($route->asArray());
                
            }
            
        }
        
        $combined = ['routes' => $array_routes, 'lookups' => $lookups];
        
        file_put_contents(
            $this->cache_file,
            '<?php
declare(strict_types=1); return '.var_export($combined, true).';'
        );
    }
    
    /**
     * Cache the underlying FastRoute route map.
     */
    private function cacheFastRouteMap()
    {
        $this->route_matcher->createCache();
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Contracts\RouteMatcher;
use Snicco\Contracts\AbstractRouteCollection;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class RouteCollection extends AbstractRouteCollection
{
    
    /**
     * A look-up table of routes by their names.
     *
     * @var Route[]
     */
    protected array        $name_list     = [];
    protected RouteMatcher $route_matcher;
    protected array        $already_added = [];
    
    public function add(Route $route) :Route
    {
        
        $this->addToCollection($route);
        return $route;
        
    }
    
    /**
     * @throws ConfigurationException
     */
    public function loadIntoDispatcher(bool $global_routes) :void
    {
        
        $this->buildLookupList();
        
        $all_routes = $this->routes;
        
        foreach ($all_routes as $method => $routes) {
            
            /** @var Route $route */
            foreach ($routes as $route) {
                
                if ($this->wasAlreadyAdded($route, $method)) {
                    continue;
                }
                
                $this->validateAttributes($route);
                
                $this->route_matcher->add($route, [$method]);
                
                $this->already_added[$method][] = $route->getUrl();
                
            }
            
        }
        
    }
    
    public function findByName(string $name) :?Route
    {
        
        $route = $this->findInLookUps($name);
        
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
        $this->prepareOutgoingRoute($routes = $this->findWildcardsInCollection($method));
        
        return $routes;
        
    }
    
    /**
     * Dont load a Route twice. This can happen if a users includes a file inside
     * globals.php or if he attempts to override an inbuilt route.
     * In this case the first route takes priority which is almost always the user-defined route.
     *
     * @param  Route  $route
     * @param  string  $method
     *
     * @return bool
     */
    private function wasAlreadyAdded(Route $route, string $method) :bool
    {
        
        if ( ! isset($this->already_added[$method])) {
            return false;
        }
        
        return in_array($route->getUrl(), $this->already_added[$method]);
        
    }
    
    /**
     * We reindex all routes by name because it's possible that a developer
     * added the name attribute to a route after it has been added to the RouteCollection.
     * By looping over all routes once we avoid that we have to loop over all routes every time a
     * developer needs to build a route url.
     */
    private function buildLookupList()
    {
        
        collect($this->routes)
            ->flatten()
            ->filter(
                fn(Route $route) => ! empty(
                    $route->getName()
                    && ! isset($this->name_list[$route->getName()])
                )
            )
            ->each(function (Route $route) {
                
                if (isset($this->name_list[$name = $route->getName()])) {
                    return;
                }
                
                $this->name_list[$name] = $route;
                
            });
        
    }
    
}
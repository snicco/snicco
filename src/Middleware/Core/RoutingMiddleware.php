<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Routing\Route;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Routing\RoutingResult;
use Psr\Http\Message\ResponseInterface;
use Snicco\Contracts\AbstractRouteCollection;

class RoutingMiddleware extends Middleware
{
    
    private AbstractRouteCollection $routes;
    
    public function __construct(AbstractRouteCollection $routes)
    {
        $this->routes = $routes;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        
        $route_result = $this->routes->match($request);
        
        if ( ! $route_result->hasRoute() || $route_result->route()->isFallback()) {
            
            $condition_route_result = $this->matchByCondition($request, $route_result);
            
        }
        
        $route_result = (isset($condition_route_result) && $condition_route_result->hasRoute())
            ? $condition_route_result
            : $route_result;
        
        return $next($request->withRoutingResult($route_result));
        
    }
    
    private function matchByCondition(Request $request, RoutingResult $route_result) :RoutingResult
    {
        
        $possible_routes = collect($this->routes->withWildCardUrl($request->getMethod()));
        
        /** @var Route|null $route */
        $route = $possible_routes->first(
            fn(Route $route) => $route->instantiateConditions()->satisfiedBy($request)
        );
        
        if ($route) {
            $route->instantiateAction();
        }
        
        return new RoutingResult($route, $route_result->capturedUrlSegmentValues());
    }
    
}



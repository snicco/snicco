<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Factories\RouteConditionFactory;
use Snicco\Contracts\RouteCollectionInterface;

class RoutingMiddleware extends Middleware
{
    
    private RouteCollectionInterface $routes;
    private RouteConditionFactory    $factory;
    
    public function __construct(RouteCollectionInterface $routes, RouteConditionFactory $factory)
    {
        $this->routes = $routes;
        $this->factory = $factory;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $route = $this->routes->matchByUrlPattern($request);
        
        if ($route && ! $route->isFallback()) {
            return $next($request->withRoute($route));
        }
        
        $fallback_route = ($route && $route->isFallback()) ? $route : null;
        
        if ( ! $route || $fallback_route) {
            $route = $this->routes->matchByConditions($request, $this->factory);
        }
        
        if ($route) {
            return $next($request->withRoute($route));
        }
        
        if ($fallback_route) {
            return $next($request->withRoute($fallback_route));
        }
        
        return $next($request);
    }
    
}



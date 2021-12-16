<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Factories\RouteConditionFactory;
use Snicco\Core\Contracts\RouteCollectionInterface;

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



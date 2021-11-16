<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Contracts\RouteBinding;
use Psr\Http\Message\ResponseInterface;

class SubstituteRouteBindings extends Middleware
{
    
    /**
     * @var RouteBinding[]
     */
    private array $route_bindings;
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        return $next($request);
    }
    
    public function prepend(RouteBinding $binding)
    {
        array_unshift($this->route_bindings, $binding);
    }
    
    public function append(RouteBinding $binding)
    {
        array_push($this->route_bindings, $binding);
    }
    
}
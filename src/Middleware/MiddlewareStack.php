<?php

declare(strict_types=1);

namespace Snicco\Middleware;

use Snicco\Support\Arr;
use Snicco\Routing\Route;
use Snicco\Http\Psr7\Request;
use Snicco\Traits\GathersMiddleware;

class MiddlewareStack
{
    
    use GathersMiddleware;
    
    private array $middleware_groups = [
        'web' => [],
        'admin' => [],
        'ajax' => [],
        'global' => [],
    ];
    
    private array $route_middleware_aliases = [];
    private array $middleware_priority      = [];
    private bool  $middleware_disabled      = false;
    
    public function createFor(Route $route, Request $request) :array
    {
        
        if ($this->middleware_disabled) {
            return [];
        }
        
        $middleware = array_diff($route->getMiddleware(), $this->middleware_groups['global']);
        
        if ($this->withGlobalMiddleware($request)) {
            
            $middleware = $this->mergeGlobalMiddleware($middleware);
            
        }
        
        $middleware = $this->expandMiddleware($middleware);
        $middleware = $this->uniqueMiddleware($middleware);
        
        return $this->sortMiddleware($middleware);
        
    }
    
    public function onlyGroups(array $groups, Request $request) :array
    {
        
        if ($this->middleware_disabled) {
            return [];
        }
        
        $middleware = $groups;
        
        if ($this->globalMiddlewareRun($request)) {
            
            Arr::pullByValue('global', $middleware);
            
        }
        
        $middleware = $this->expandMiddleware($middleware);
        $middleware = $this->uniqueMiddleware($middleware);
        
        return $this->sortMiddleware($middleware);
        
    }
    
    public function withMiddlewareGroup(string $group, array $middlewares)
    {
        
        $this->middleware_groups[$group] = $middlewares;
        
    }
    
    public function middlewarePriority(array $middleware_priority)
    {
        
        $this->middleware_priority = $middleware_priority;
        
    }
    
    public function middlewareAliases(array $route_middleware_aliases)
    {
        
        $this->route_middleware_aliases = $route_middleware_aliases;
        
    }
    
    public function disableAllMiddleware()
    {
        $this->middleware_disabled = true;
    }
    
    private function withGlobalMiddleware(Request $request) :bool
    {
        
        return ! $request->getAttribute('global_middleware_run', false);
        
    }
    
    private function globalMiddlewareRun(Request $request) :bool
    {
        
        return $request->getAttribute('global_middleware_run', false);
        
    }
    
}
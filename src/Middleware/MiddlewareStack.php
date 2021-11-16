<?php

declare(strict_types=1);

namespace Snicco\Middleware;

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
    private bool  $always_with_core_middleware;
    
    public function __construct(bool $always_with_core_middleware = false)
    {
        $this->always_with_core_middleware = $always_with_core_middleware;
    }
    
    public function createForRoute(Route $route) :array
    {
        if ($this->middleware_disabled) {
            return [];
        }
        
        $middleware = $route->getMiddleware();
        $middleware[] = 'global';
        $middleware = array_diff($middleware, $this->middleware_groups['global']);
        
        $middleware = $this->expandMiddleware($middleware);
        $middleware = $this->uniqueMiddleware($middleware);
        
        return $this->sortMiddleware($middleware, $this->middleware_priority);
    }
    
    public function createForRequestWithoutRoute(Request $request, bool $force_include_global = false) :array
    {
        $middleware =
            $this->expandMiddleware($this->coreMiddleware($request, $force_include_global));
        $middleware = $this->uniqueMiddleware($middleware);
        
        return $this->sortMiddleware($middleware, $this->middleware_priority);
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
        $this->route_middleware_aliases =
            array_merge($this->route_middleware_aliases, $route_middleware_aliases);
    }
    
    public function disableAllMiddleware()
    {
        $this->middleware_disabled = true;
    }
    
    private function coreMiddleware(Request $request, bool $force_include_global = false) :array
    {
        if ($this->middleware_disabled) {
            return [];
        }
        
        if ( ! $this->always_with_core_middleware) {
            return $force_include_global ? ['global'] : [];
        }
        
        $middleware = ['global'];
        
        if ($request->isWpFrontEnd()) {
            $middleware[] = 'web';
        }
        elseif ($request->isWpAdmin()) {
            $middleware[] = 'admin';
        }
        elseif ($request->isWpAjax()) {
            $middleware[] = 'ajax';
        }
        
        return $middleware;
    }
    
}
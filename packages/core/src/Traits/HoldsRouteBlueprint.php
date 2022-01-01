<?php

declare(strict_types=1);

namespace Snicco\Core\Traits;

use LogicException;
use Snicco\Support\Arr;
use Snicco\Core\Support\Url;
use Snicco\Core\Support\Path;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\MenuItem;
use Snicco\Core\Routing\Internal\AdminDashboardRequest;

trait HoldsRouteBlueprint
{
    
    private array $delegate_attributes = [];
    
    public function get(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['GET', 'HEAD'], $action);
    }
    
    public function admin(string $name, string $path, $action = null, MenuItem $menu_item = null) :Route
    {
        if ($this->hasGroup() && $this->currentGroup()->prefix()->asString() !== '/') {
            throw new LogicException(
                "Its not possible to add a prefix to admin route [$name]."
            );
        }
        
        $path = Url::combineRelativePath($this->admin_path->urlPrefix(), $path);
        $route = $this->get($name, $path, $action);
        
        $route->condition(AdminDashboardRequest::class);
        
        return $route;
    }
    
    public function post(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['POST'], $action);
    }
    
    public function put(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['PUT'], $action);
    }
    
    public function patch(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['PATCH'], $action);
    }
    
    public function delete(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['DELETE'], $action);
    }
    
    public function options(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, ['OPTIONS'], $action);
    }
    
    public function any(string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, Route::ALL_METHODS, $action);
    }
    
    public function match(array $verbs, string $name, string $path, $action = null) :Route
    {
        return $this->registerRoute($name, $path, array_map('strtoupper', $verbs), $action);
    }
    
    /**
     * @param  string|array<string>  $middleware
     */
    public function middleware($middleware) :self
    {
        $this->delegate_attributes['middleware'] = Arr::wrap($middleware);
        return $this;
    }
    
    public function name(string $name) :self
    {
        $this->delegate_attributes['name'] = $name;
        return $this;
    }
    
    public function prefix(string $prefix) :self
    {
        $this->delegate_attributes['prefix'] = Path::fromString($prefix);
        return $this;
    }
    
    public function namespace(string $namespace) :self
    {
        $this->delegate_attributes['namespace'] = $namespace;
        return $this;
    }
    
}
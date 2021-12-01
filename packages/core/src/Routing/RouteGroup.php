<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Arr;
use Snicco\Support\Url;

class RouteGroup
{
    
    private string $namespace;
    private string $url_prefix;
    private string $name;
    private array  $middleware;
    private array  $conditions;
    private array  $methods;
    private ?bool  $no_action;
    
    public function __construct(array $attributes = [])
    {
        $this->namespace = Arr::get($attributes, 'namespace', '');
        $this->url_prefix = Arr::get($attributes, 'prefix', '');
        $this->name = Arr::get($attributes, 'name', '');
        $this->middleware = Arr::get($attributes, 'middleware', []);
        $this->conditions = Arr::get($attributes, 'where', []);
        $this->methods = Arr::wrap(Arr::get($attributes, 'methods', []));
        $this->no_action = Arr::get($attributes, 'noAction');
    }
    
    public function mergeWith(RouteGroup $old_group) :RouteGroup
    {
        $this->methods = $this->mergeMethods($old_group->methods);
        
        $this->middleware = $this->mergeMiddleware($old_group->middleware);
        
        $this->name = $this->mergeName($old_group->name);
        
        $this->url_prefix = $this->mergePrefix($old_group->url_prefix);
        
        $this->conditions = $this->mergeConditions($old_group->conditions);
        
        $this->no_action = $this->mergeNoAction($old_group->no_action);
        
        return $this;
    }
    
    public function mergeIntoRoute(Route $route)
    {
        if ($methods = $this->methods) {
            $route->methods($methods);
        }
        
        if ($middleware = $this->middleware) {
            $route->middleware($middleware);
        }
        
        if ($namespace = $this->namespace) {
            $route->namespace($namespace);
        }
        
        if ($name = $this->name) {
            $route->name($name);
        }
        
        if (count($this->conditions)) {
            foreach ($this->conditions as $condition) {
                $route->where(array_shift($condition), ...$condition);
            }
        }
        
        if ($this->no_action && ! $route->getAction()) {
            $route->noAction();
        }
    }
    
    public function prefix()
    {
        return $this->url_prefix;
    }
    
    private function mergeMethods(array $old_methods) :array
    {
        return array_merge($old_methods, $this->methods);
    }
    
    private function mergeMiddleware(array $old_middleware) :array
    {
        return array_merge($old_middleware, $this->middleware);
    }
    
    private function mergeName(string $old) :string
    {
        // Remove leading and trailing dots.
        $new = preg_replace('/^\.+|\.+$/', '', $this->name);
        $old = preg_replace('/^\.+|\.+$/', '', $old);
        
        return trim($old.'.'.$new, '.');
    }
    
    private function mergePrefix(string $old_group_prefix) :string
    {
        return Url::combineRelativePath($old_group_prefix, $this->url_prefix);
    }
    
    private function mergeConditions(array $old_conditions) :array
    {
        foreach ($this->conditions as $condition) {
            $old_conditions[] = $condition;
        }
        return $old_conditions;
    }
    
    private function mergeNoAction($old_group_no_action)
    {
        if (is_null($this->no_action)) {
            return $old_group_no_action;
        }
        
        return $this->no_action;
    }
    
}
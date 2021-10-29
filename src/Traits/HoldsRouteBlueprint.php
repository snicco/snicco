<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Snicco\Support\Arr;
use Snicco\Routing\Route;
use Snicco\Routing\ConditionBucket;

trait HoldsRouteBlueprint
{
    
    private array $delegate_attributes = [];
    
    public function get(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['GET', 'HEAD'], $url, $action);
        
    }
    
    public function post(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['POST'], $url, $action);
        
    }
    
    public function put(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['PUT'], $url, $action);
        
    }
    
    public function patch(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['PATCH'], $url, $action);
        
    }
    
    public function delete(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['DELETE'], $url, $action);
        
    }
    
    public function options(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(['OPTIONS'], $url, $action);
        
    }
    
    public function any(string $url = '*', $action = null) :Route
    {
        
        return $this->addRoute(
            ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            $url,
            $action
        );
    }
    
    public function match($verbs, $url, $action = null) :Route
    {
        
        $verbs = Arr::wrap($verbs);
        
        return $this->addRoute(array_map('strtoupper', $verbs), $url, $action);
        
    }
    
    /**
     * @param  string|array  $middleware
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
        $this->delegate_attributes['prefix'] = $prefix;
        return $this;
    }
    
    /**
     * @param  mixed  $args,...
     */
    public function where(...$args) :self
    {
        
        // NOTE: $args === func_get_args()
        if ( ! isset($this->delegate_attributes['where'])) {
            $this->delegate_attributes['where'] = ConditionBucket::createEmpty();
        }
        
        /** @var ConditionBucket $conditions */
        $conditions = $this->delegate_attributes['where'];
        $conditions->add($args);
        
        return $this;
    }
    
    public function noAction() :self
    {
        $this->delegate_attributes['noAction'] = true;
        return $this;
    }
    
    public function namespace(string $namespace) :self
    {
        $this->delegate_attributes['namespace'] = $namespace;
        return $this;
    }
    
    /**
     * @param  array|string  $methods
     *
     * @return $this
     */
    public function methods($methods) :self
    {
        $this->delegate_attributes['methods'] = Arr::wrap($methods);
        return $this;
    }
    
}
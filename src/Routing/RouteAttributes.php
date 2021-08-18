<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\WP;
use Snicco\Support\Arr;
use Snicco\Support\UrlParser;
use Snicco\Contracts\SetsRouteAttributes;

class RouteAttributes implements SetsRouteAttributes
{
    
    private Route $route;
    
    public function __construct(Route $route)
    {
        
        $this->route = $route;
    }
    
    public function populateInitial(array $attributes)
    {
        
        if ($methods = Arr::get($attributes, 'methods')) {
            
            $this->methods($methods);
            
        }
        
        if ($middleware = Arr::get($attributes, 'middleware')) {
            
            $this->middleware($middleware);
            
        }
        
        if ($namespace = Arr::get($attributes, 'namespace')) {
            
            $this->namespace($namespace);
            
        }
        
        if ($name = Arr::get($attributes, 'name')) {
            
            $this->name($name);
            
        }
        
        if ($conditions = Arr::get($attributes, 'where')) {
            
            foreach ($conditions->all() as $condition) {
                
                $this->where($condition);
                
            }
            
        }
        
        if (Arr::get($attributes, 'noAction')) {
            
            $this->noAction();
            
        }
        
    }
    
    public function methods($methods)
    {
        
        $this->route->methods($methods);
        
    }
    
    public function middleware($middleware)
    {
        
        $this->route->middleware($middleware);
        
    }
    
    public function namespace(string $namespace)
    {
        
        $this->route->namespace($namespace);
        
    }
    
    public function name(string $name)
    {
        
        $this->route->name($name);
        
    }
    
    public function where()
    {
        
        $args = func_get_args();
        
        $args = Arr::flattenOnePreserveKeys($args);
        
        $this->route->where(...$args);
        
    }
    
    public function noAction()
    {
        
        if ( ! $this->route->getAction()) {
            
            $this->route->noAction();
            
        }
        
    }
    
    public function mergeGroup(RouteGroup $group)
    {
        
        if ($methods = $group->methods()) {
            
            $this->methods($methods);
            
        }
        
        if ($middleware = $group->middleware()) {
            
            $this->middleware($middleware);
            
        }
        
        if ($namespace = $group->namespace()) {
            
            $this->namespace($namespace);
            
        }
        
        if ($name = $group->name()) {
            
            $this->name($name);
            
        }
        
        if ($condition_bucket = $group->conditions()) {
            
            $conditions = $condition_bucket->all();
            
            if (trim($group->prefix(), '/') === WP::wpAdminFolder()) {
                
                $page = UrlParser::getPageQueryVar($this->route->getUrl());
                
                $conditions[] = [
                    'admin_page',
                    ['page' => trim($page, '/')],
                ];
                
            }
            
            if (trim($group->prefix(), '/') === WP::ajaxUrl()) {
                
                $action = UrlParser::getAjaxAction($this->route->getUrl());
                
                $conditions[] = [
                    'admin_ajax',
                    ['action' => $action],
                ];
                
            }
            
            foreach ($conditions as $condition) {
                
                $this->where($condition);
                
            }
            
        }
        
        if ($group->no_action === true) {
            
            $this->noAction();
            
        }
        
    }
    
    public function defaults(array $defaults)
    {
        
        $this->route->defaults($defaults);
        
    }
    
}
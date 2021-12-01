<?php

declare(strict_types=1);

namespace Snicco\Routing;

class RouteCache
{
    
    private array   $cache;
    private ?string $cache_file;
    
    public function __construct(?string $cache_file = null)
    {
        $this->cache_file = $cache_file;
        
        if ($cache_file && file_exists($cache_file)) {
            $this->cache = require $cache_file;
        }
        else {
            $this->cache = [];
        }
    }
    
    public function created() :bool
    {
        return count($this->cache) > 0;
    }
    
    public function urlRoutes()
    {
        return $this->cache['url_routes'];
    }
    
    public function nameList()
    {
        return $this->cache['name_list'];
    }
    
    public function conditionRoutes()
    {
        return $this->cache['condition_routes'];
    }
    
    public function routeUrlData()
    {
        return $this->cache['route_url_data'];
    }
    
    /**
     * @param  array  $url_routes  Keyed by methods
     * @param  array  $condition_routes  Keyed by methods
     * @param  array  $named_routes
     * @param  array  $route_url_data
     */
    public function create(array $url_routes, array $condition_routes, array $named_routes, array $route_url_data)
    {
        $_name_list = array_filter($named_routes, function (Route $route) {
            return ! empty($route->getName());
        });
        
        $_name_list = array_map(function (Route $route) {
            return $route->asArray();
        }, $_name_list);
        
        $_url_routes = [];
        
        foreach ($url_routes as $method => $routes) {
            /** @var Route $route */
            foreach ($routes as $route) {
                $_url_routes[$method][] = $route->asArray();
            }
        }
        
        $_condition_routes = [];
        
        foreach ($condition_routes as $method => $routes) {
            /** @var Route $route */
            foreach ($routes as $route) {
                $_condition_routes[$method][] = $route->asArray();
            }
        }
        
        $combined = [
            'url_routes' => $_url_routes,
            'condition_routes' => $_condition_routes,
            'name_list' => $_name_list,
            'route_url_data' => $route_url_data,
        ];
        
        file_put_contents(
            $this->cache_file,
            '<?php
        declare(strict_types=1); return '.var_export($combined, true).';'
        );
    }
    
}
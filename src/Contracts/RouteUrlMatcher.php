<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Routing\Route;
use Snicco\Routing\RoutingResult;

interface RouteUrlMatcher
{
    
    public function add(Route $route, array $methods);
    
    public function find(string $method, string $path) :RoutingResult;
    
    public function loadDataFromCache($cache_data);
    
    /**
     * The cacheable data SHALL NOT contain any object or closures.
     * Use the $route->asArray() method appropriately
     *
     * @return mixed
     * @see Route::asArray()
     */
    public function getCacheableData();
    
    public function isCacheable() :bool;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Route;

use ArrayIterator;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

use function count;

/**
 * @interal
 */
class RouteCollection implements Routes
{
    
    /**
     * @var array<string,Route>
     */
    private array $routes = [];
    
    public function add(Route $route) :void
    {
        $this->routes[$route->getName()] = $route;
    }
    
    public function getByName(string $name) :Route
    {
        if ( ! isset($this->routes[$name])) {
            throw RouteNotFound::name($name);
        }
        return $this->routes[$name];
    }
    
    /**
     * @return Route[]|ArrayIterator An \ArrayIterator object for iterating over routes
     */
    public function getIterator() :ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }
    
    public function count() :int
    {
        return count($this->routes);
    }
    
}
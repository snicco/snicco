<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Route;

use ArrayIterator;
use Webmozart\Assert\Assert;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

use function count;

/**
 * @interal
 */
final class RouteCollection implements Routes
{
    
    /**
     * @var array<Route>
     */
    private array $routes = [];
    
    /**
     * @param  array<Route>  $routes
     */
    public function __construct(array $routes)
    {
        foreach ($routes as $route) {
            Assert::isInstanceOf($route, Route::class);
            
            $name = $route->getName();
            
            Assert::keyNotExists(
                $this->routes,
                $name,
                sprintf(
                    "Duplicate route with name [%s] while create [%s].",
                    $name,
                    RouteCollection::class
                )
            );
            $this->routes[$name] = $route;
        }
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
        return new ArrayIterator($this->toArray());
    }
    
    public function count() :int
    {
        return count($this->routes);
    }
    
    public function toArray() :array
    {
        return $this->routes;
    }
    
}
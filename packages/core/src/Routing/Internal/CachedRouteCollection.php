<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use ArrayIterator;
use RuntimeException;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\Routes;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

use function count;
use function unserialize;

/**
 * @interal
 */
final class CachedRouteCollection implements Routes
{
    
    /**
     * @var array<string,string>
     */
    private array $serialized_routes;
    
    /**
     * @var array<string,Route>
     */
    private array $hydrated_routes;
    
    /**
     * @param  array<string,string>  $serialized_routes
     */
    public function __construct(array $serialized_routes)
    {
        $this->serialized_routes = $serialized_routes;
        $this->hydrated_routes = [];
    }
    
    public function getIterator() :ArrayIterator
    {
        if (count($this->hydrated_routes) === count($this->serialized_routes)) {
            return new ArrayIterator($this->hydrated_routes);
        }
        $routes = [];
        foreach ($this->serialized_routes as $name => $route) {
            $routes[$name] = unserialize($route);
        }
        $this->hydrated_routes = $routes;
        return new ArrayIterator($this->hydrated_routes);
    }
    
    public function count() :int
    {
        return count($this->serialized_routes);
    }
    
    public function add(Route $route) :void
    {
        throw new RuntimeException(
            sprintf(
                'Route [%s] cant be added because the route collection is already cached.',
                $route->getName()
            )
        );
    }
    
    public function getByName(string $name) :Route
    {
        if (isset($this->hydrated_routes[$name])) {
            return $this->hydrated_routes[$name];
        }
        
        if (isset($this->serialized_routes[$name])) {
            $this->hydrated_routes[$name] = unserialize($this->serialized_routes[$name]);
            return $this->hydrated_routes[$name];
        }
        
        throw RouteNotFound::name($name);
    }
    
}
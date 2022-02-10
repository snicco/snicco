<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use ArrayIterator;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Traversable;

use function count;

final class RuntimeRouteCollection extends RouteCollection
{

    /**
     * @var array<string,Route>
     */
    private array $routes = [];

    public function getByName(string $name): Route
    {
        if (!isset($this->routes[$name])) {
            throw RouteNotFound::name($name);
        }
        return $this->routes[$name];
    }

    public function toArray(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function add(Route $route): void
    {
        $this->routes[$route->getName()] = $route;
    }
}
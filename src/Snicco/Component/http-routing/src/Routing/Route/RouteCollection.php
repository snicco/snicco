<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use ArrayIterator;
use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Traversable;

use function count;

/**
 * @psalm-immutable
 *
 * @psalm-internal Snicco\Component\HttpRouting
 * @interal
 */
final class RouteCollection implements Routes
{
    /**
     * @var array<string,Route>
     */
    private array $routes = [];

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes = [])
    {
        foreach ($routes as $route) {
            $name = $route->getName();
            if (isset($this->routes[$name])) {
                throw new InvalidArgumentException(sprintf('Duplicate route name [%s].', $name));
            }

            $this->routes[$name] = $route;
        }
    }

    public function getByName(string $name): Route
    {
        if (! isset($this->routes[$name])) {
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
        return new ArrayIterator($this->routes);
    }
}

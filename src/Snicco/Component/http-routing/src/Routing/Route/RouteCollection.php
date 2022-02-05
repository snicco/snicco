<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use ArrayIterator;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Traversable;
use Webmozart\Assert\Assert;

use function count;

/**
 * @internal
 */
final class RouteCollection implements Routes
{

    /**
     * @var array<string,Route>
     */
    private array $routes = [];

    /**
     * @param array<Route> $routes
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
                    'Duplicate route with name [%s] while create [%s].',
                    $name,
                    RouteCollection::class
                )
            );
            $this->routes[$name] = $route;
        }
    }

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
}
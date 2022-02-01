<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use ArrayIterator;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Webmozart\Assert\Assert;

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
     * @param array<string,string> $serialized_routes
     */
    public function __construct(array $serialized_routes)
    {
        Assert::allString(
            $serialized_routes,
            'The cached route collection can only contain serialized routes.'
        );

        $this->serialized_routes = $serialized_routes;
        $this->hydrated_routes = [];
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    public function toArray(): array
    {
        if ($this->isFullyHydrated()) {
            return $this->hydrated_routes;
        }

        $routes = $this->hydrateAll();

        $this->hydrated_routes = $routes;

        return $this->hydrated_routes;
    }

    private function isFullyHydrated(): bool
    {
        return count($this->hydrated_routes) === count($this->serialized_routes);
    }

    private function hydrateAll(): array
    {
        $_routes = [];

        foreach ($this->serialized_routes as $name => $route) {
            if (isset($this->hydrated_routes[$name])) {
                $_routes[$name] = $this->hydrated_routes[$name];
                continue;
            }

            $route = unserialize($route);

            $this->checkIsValidRoute($route);
            $this->checkValidName($name, $route);

            $_routes[$name] = $route;
        }
        return $_routes;
    }

    private function checkIsValidRoute($route)
    {
        Assert::isInstanceOf(
            $route,
            Route::class,
            sprintf(
                "Your route cache seems corrupted.\nThe cached route collection contained a serialized of type [%s].",
                is_object($route) ? get_class($route) : gettype($route)
            )
        );
    }

    private function checkValidName(string $used_name, Route $route)
    {
        if ($route->getName() !== $used_name) {
            throw RouteNotFound::accessByBadName($used_name, $route->getName());
        }
    }

    public function count(): int
    {
        return count($this->serialized_routes);
    }

    public function getByName(string $name): Route
    {
        if (isset($this->hydrated_routes[$name])) {
            return $this->hydrated_routes[$name];
        }

        if (isset($this->serialized_routes[$name])) {
            $route = unserialize($this->serialized_routes[$name]);

            $this->checkIsValidRoute($route);
            $this->checkValidName($name, $route);

            $this->hydrated_routes[$name] = $route;

            return $route;
        }

        throw RouteNotFound::name($name);
    }

}
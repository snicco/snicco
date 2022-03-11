<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use ArrayIterator;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Traversable;
use Webmozart\Assert\Assert;

use function count;
use function get_class;
use function gettype;
use function is_object;
use function unserialize;

/**
 * @psalm-external-mutation-free
 *
 * @psalm-internal Snicco\Component\HttpRouting
 * @interal
 */
final class SerializedRouteCollection implements Routes
{
    /**
     * @var array<string,string>
     */
    private array $serialized_routes = [];

    /**
     * @var array<string,Route>
     */
    private array $hydrated_routes = [];

    /**
     * @param array<string,string> $serialized_routes
     */
    public function __construct(array $serialized_routes)
    {
        $this->serialized_routes = $serialized_routes;
        $this->hydrated_routes = [];
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

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    private function isFullyHydrated(): bool
    {
        return count($this->hydrated_routes) === count($this->serialized_routes);
    }

    /**
     * @return array<string,Route>
     */
    private function hydrateAll(): array
    {
        $_routes = [];

        foreach ($this->serialized_routes as $name => $route) {
            if (isset($this->hydrated_routes[$name])) {
                $_routes[$name] = $this->hydrated_routes[$name];

                continue;
            }

            /** @var false|mixed|Route $route */
            $route = unserialize($route);

            $this->checkIsValidRoute($route);
            $this->checkValidName($name, $route);

            $_routes[$name] = $route;
        }

        return $_routes;
    }

    /**
     * @psalm-assert Route $route
     *
     * @param mixed $route
     */
    private function checkIsValidRoute($route): void
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

    private function checkValidName(string $used_name, Route $route): void
    {
        if ($route->getName() !== $used_name) {
            throw RouteNotFound::accessByBadName($used_name, $route->getName());
        }
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing;

use Closure;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Traversable;

use function count;

/**
 * @interal
 *
 * The Router implements and partially delegates all core parts of the Routing system.
 * This is preferred over passing around one (global) instance of {@see Routes} between different
 * objects.
 */
final class Router implements UrlMatcher, UrlGeneratorInterface, Routes
{

    private AdminArea $admin_area;

    /**
     * @var Closure(Routes):UrlGeneratorInterface
     */
    private Closure $generator_factory;
    private ?UrlGeneratorInterface $generator = null;
    private RouteCollection $route_collection;
    private FastRouteDispatcher $dispatcher;

    /**
     * @param Closure(Routes):UrlGeneratorInterface $generator_factory
     */
    public function __construct(
        RouteCollection $route_collection,
        Closure $generator_factory,
        AdminArea $admin_area,
        FastRouteDispatcher $dispatcher
    ) {
        $this->route_collection = $route_collection;
        $this->generator_factory = $generator_factory;
        $this->admin_area = $admin_area;
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(Request $request): RoutingResult
    {
        $request = $this->allowMatchingAdminDashboardRequests($request);

        return $this->dispatcher->dispatch($request);
    }

    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null): string
    {
        return $this->getGenerator()->to($path, $extra, $type, $secure);
    }

    public function toRoute(
        string $name,
        array $arguments = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $secure = null
    ): string {
        return $this->getGenerator()->toRoute($name, $arguments, $type, $secure);
    }

    public function secure(string $path, array $extra = []): string
    {
        return $this->getGenerator()->secure($path, $extra);
    }

    public function canonical(): string
    {
        return $this->getGenerator()->canonical();
    }

    public function full(): string
    {
        return $this->getGenerator()->full();
    }

    public function previous(string $fallback = '/'): string
    {
        return $this->getGenerator()->previous($fallback);
    }

    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string
    {
        return $this->getGenerator()->toLogin($arguments, $type);
    }

    public function getIterator(): Traversable
    {
        return $this->getRoutes()->getIterator();
    }

    public function count(): int
    {
        return count($this->getRoutes());
    }

    public function getByName(string $name): Route
    {
        return $this->getRoutes()->getByName($name);
    }

    public function toArray(): array
    {
        return $this->getRoutes()->toArray();
    }

    private function getRoutes(): Routes
    {
        return $this->route_collection;
    }

    private function allowMatchingAdminDashboardRequests(Request $request): Request
    {
        if (!$request->isGet()) {
            return $request;
        }

        if (!$request->isToAdminArea()) {
            return $request;
        }

        $uri = $request->getUri();
        $new_uri = $uri->withPath($this->admin_area->rewriteForRouting($request));

        return $request->withUri($new_uri);
    }

    private function getGenerator(): UrlGeneratorInterface
    {
        if (!isset($this->generator)) {
            $closure = $this->generator_factory;
            $this->generator = $closure($this->getRoutes());
        }

        return $this->generator;
    }

}


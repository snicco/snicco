<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing;

use Closure;
use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Cache\RouteCacheInterface;
use Snicco\Component\HttpRouting\Routing\Condition\IsAdminDashboardRequest;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\CachedRouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\Route\RuntimeRouteCollection;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteSyntaxConverter;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RouteGroup;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Traversable;
use Webmozart\Assert\Assert;

use function array_pop;
use function array_reverse;
use function count;
use function is_array;
use function trim;

/**
 * @interal
 *
 * The Router implements and partially delegates all core parts of the Routing system.
 * This is preferred over passing around one (global) instance of {@see Routes} between different
 * objects.
 */
final class Router implements UrlMatcher, UrlGeneratorInterface, Routes
{

    private RouteConditionFactory $condition_factory;
    private AdminArea $admin_area;

    /**
     * @var Closure(Routes):UrlGeneratorInterface
     */
    private Closure $generator_factory;

    /**
     * @var list<RouteGroup>
     */
    private array $group_stack = [];

    private ?UrlGeneratorInterface $generator = null;
    private RouteCacheInterface $cache;
    private RouteCollection $route_collection;

    /**
     * @param Closure(Routes):UrlGeneratorInterface $generator_factory
     */
    public function __construct(
        RouteConditionFactory $condition_factory,
        Closure $generator_factory,
        AdminArea $admin_area,
        RouteCacheInterface $cache
    ) {
        $this->condition_factory = $condition_factory;
        $this->admin_area = $admin_area;
        $this->generator_factory = $generator_factory;
        $this->cache = $cache;

        $data = $this->cache->get();

        if (is_array($data)) {
            $this->route_collection = new CachedRouteCollection($data['route_collection']);
            $this->fast_route_data = $data['fast_route'];
        } else {
            $this->route_collection = new RuntimeRouteCollection();
        }
    }

    /**
     * @interal
     *
     * @param array{namespace?:string, prefix?:string|UrlPath, name?:string, middleware?: string|string[]} $attributes
     */
    public function createInGroup(
        RoutingConfigurator $routing_configurator,
        Closure $create_routes,
        array $attributes
    ): void {
        $this->updateGroupStack(new RouteGroup($attributes));

        $create_routes($routing_configurator);

        $this->deleteCurrentGroup();
    }

    /**
     * @interal
     *
     * @param string|class-string|array{0:class-string, 1:string} $controller
     */
    public function registerAdminRoute(string $name, string $path, $controller = Route::DELEGATE): Route
    {
        $route = $this->createRoute($name, $path, ['GET'], $controller);
        $route->condition(IsAdminDashboardRequest::class);
        return $route;
    }

    /**
     * @interal
     *
     * @param string|class-string|array{0:class-string, 1:string} $controller
     * @param string[] $methods
     */
    public function registerWebRoute(string $name, string $path, array $methods, $controller): Route
    {
        return $this->createRoute($name, $path, $methods, $controller);
    }

    public function dispatch(Request $request): RoutingResult
    {
        $data = $this->getFastRouteData();

        $request = $this->allowMatchingAdminDashboardRequests($request);

        return (new FastRouteDispatcher(
            $this->getRoutes(), $data, $this->condition_factory
        ))->dispatch($request);
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

    private function updateGroupStack(RouteGroup $group): void
    {
        $current = $this->currentGroup();
        if ($current) {
            $group = $group->mergeWith($current);
        }

        $this->group_stack[] = $group;
    }

    private function currentGroup(): ?RouteGroup
    {
        if (!count($this->group_stack)) {
            return null;
        }
        return array_reverse($this->group_stack)[0];
    }

    private function deleteCurrentGroup(): void
    {
        array_pop($this->group_stack);
    }

    /**
     * @param string|class-string|array{0: class-string, 1: string} $controller
     * @param string[] $methods
     */
    private function createRoute(string $name, string $path, array $methods, $controller): Route
    {
        // Quick check to see if the developer swapped the arguments by accident.
        Assert::notStartsWith($name, '/');

        $path = $this->applyGroupPrefix(UrlPath::fromString($path));
        $name = $this->applyGroupName($name);
        $namespace = $this->applyGroupNamespace();

        $route = Route::create(
            $path->asString(),
            $controller,
            $name,
            $methods,
            $namespace
        );

        $this->addGroupAttributes($route);

        $this->route_collection->add($route);

        return $route;
    }

    private function applyGroupPrefix(UrlPath $path): UrlPath
    {
        $current = $this->currentGroup();
        if (!$current) {
            return $path;
        }

        return $path->prepend($current->prefix);
    }

    private function applyGroupName(string $route_name): string
    {
        $current = $this->currentGroup();
        if (!$current) {
            return $route_name;
        }

        $g = trim($current->name, '.');

        if ($g === '') {
            return $route_name;
        }

        return "$g.$route_name";
    }

    private function applyGroupNamespace(): string
    {
        $current = $this->currentGroup();
        if (!$current) {
            return '';
        }

        return $current->namespace;
    }

    private function addGroupAttributes(Route $route): void
    {
        $current = $this->currentGroup();
        if (!$current) {
            return;
        }

        foreach ($current->middleware as $middleware) {
            $route->middleware($middleware);
        }
    }

    private function getFastRouteData(): array
    {
        $collector = new RouteCollector(new RouteParser(), new DataGenerator());
        $syntax = new FastRouteSyntaxConverter();

        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            $path = $syntax->convert($route);
            try {
                $collector->addRoute($route->getMethods(), $path, $route->getName());
            } catch (BadRouteException $e) {
                throw BadRouteConfiguration::fromPrevious($e);
            }
        }

        return $collector->getData();
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


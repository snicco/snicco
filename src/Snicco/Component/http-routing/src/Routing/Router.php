<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing;

use Closure;
use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use LogicException;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminArea;
use Snicco\Component\HttpRouting\Routing\Condition\IsAdminDashboardRequest;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\CachedRouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteSyntaxConverter;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RouteGroup;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\ValueObject\PHPCacheFile;
use Traversable;
use Webmozart\Assert\Assert;

use function array_pop;
use function array_reverse;
use function count;
use function file_put_contents;
use function serialize;
use function trim;
use function var_export;

/**
 * @api
 * The Router implements and partially delegates all core parts of the Routing system.
 * This is preferred over passing around one (global) instance of {@see Routes} between different
 * objects in the service container.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 */
final class Router implements UrlMatcher, UrlGeneratorInterface, Routes
{

    private RouteConditionFactory $condition_factory;
    private AdminArea $admin_area;
    private UrlGeneratorFactory $generator_factory;
    private ?PHPCacheFile $cache_file;
    private CachedRouteCollection $cached_routes;
    private UrlGeneratorInterface $generator;

    /**
     * @var list<RouteGroup>
     */
    private array $group_stack = [];

    private array $fast_route_cache = [];

    /**
     * @var array<string,Route>
     */
    private array $_routes = [];

    /**
     * @api
     */
    public function __construct(
        RouteConditionFactory $condition_factory,
        UrlGeneratorFactory $generator_factory,
        AdminArea $admin_area,
        PHPCacheFile $cache_file = null
    ) {
        $this->cache_file = $cache_file;
        $this->condition_factory = $condition_factory;
        $this->admin_area = $admin_area;

        if ($this->cache_file && $this->cache_file->isCreated()) {
            $cache = $this->cache_file->require();
            Assert::isArray($cache);
            Assert::keyExists($cache, 'route_collection');
            Assert::keyExists($cache, 'fast_route');
            Assert::isArray($cache['fast_route']);
            Assert::isArray($cache['route_collection']);
            $this->fast_route_cache = $cache['fast_route'];
            /** @var array<string,string> $routes */
            $routes = $cache['route_collection'];
            $this->cached_routes = new CachedRouteCollection($routes);
        }

        $this->generator_factory = $generator_factory;
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

        if ($this->cache_file && !$this->cache_file->isCreated()) {
            $this->createCache($data);
        }

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
        if ($this->cache_file && $this->cache_file->isCreated()) {
            throw new LogicException(
                "The route [$name] cant be added because the Router is already cached."
            );
        }

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

        $this->_routes[$route->getName()] = $route;

        return $route;
    }

    private function applyGroupPrefix(UrlPath $path): UrlPath
    {
        $current = $this->currentGroup();
        if (!$current) {
            return $path;
        }

        return $path->prepend($current->prefix());
    }

    private function applyGroupName(string $route_name): string
    {
        $current = $this->currentGroup();
        if (!$current) {
            return $route_name;
        }

        $g = trim($current->name(), '.');

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

        return $current->namespace();
    }

    private function addGroupAttributes(Route $route): void
    {
        $current = $this->currentGroup();
        if (!$current) {
            return;
        }

        foreach ($current->middleware() as $middleware) {
            $route->middleware($middleware);
        }
    }

    private function getFastRouteData(): array
    {
        if (count($this->fast_route_cache)) {
            return $this->fast_route_cache;
        }

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
        return $this->cached_routes ?? new RouteCollection($this->_routes);
    }

    /** @psalm-suppress PossiblyNullReference */
    private function createCache(array $fast_route_data): void
    {
        $_r = [];

        foreach ($this->getRoutes() as $name => $route) {
            $_r[$name] = serialize($route);
        }

        $arr = [
            'route_collection' => $_r,
            'fast_route' => $fast_route_data,
        ];
        $res = file_put_contents(
            $this->cache_file->realPath(),
            '<?php return ' . var_export($arr, true) . ';'
        );

        if ($res === false) {
            throw new RuntimeException('Could not write route cache file.');
        }
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
            $this->generator = $this->generator_factory->create($this->getRoutes());
        }

        return $this->generator;
    }

}


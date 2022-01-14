<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Closure;
use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use FastRoute\RouteCollector;
use Snicco\Core\Utils\UrlPath;
use FastRoute\BadRouteException;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Utils\PHPCacheFile;
use Snicco\Core\Routing\Route\Route;
use Snicco\Core\Routing\Route\Routes;
use FastRoute\RouteParser\Std as RouteParser;
use Snicco\Core\Routing\UrlMatcher\RouteGroup;
use Snicco\Core\Routing\UrlMatcher\UrlMatcher;
use Snicco\Core\Routing\Route\RouteCollection;
use Snicco\Core\Routing\UrlMatcher\RoutingResult;
use Snicco\Core\Routing\AdminDashboard\AdminArea;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\Routing\Route\CachedRouteCollection;
use Snicco\Core\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Core\Routing\Exception\BadRouteConfiguration;
use Snicco\Core\Routing\Condition\RouteConditionFactory;
use Snicco\Core\Routing\UrlGenerator\UrlGeneratorFactory;
use Snicco\Core\Routing\Condition\IsAdminDashboardRequest;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Core\Routing\UrlMatcher\FastRouteSyntaxConverter;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;

use function trim;
use function count;
use function serialize;
use function array_pop;
use function var_export;
use function array_reverse;
use function file_put_contents;

/**
 * @interal
 * The Router implements and partially delegates all core parts of the Routing system.
 * This is preferred over passing around one (global) instance of {@see Routes} between different
 * objects in the service container.
 */
final class Router implements UrlMatcher, UrlGenerator, Routes
{
    
    private RouteConditionFactory $condition_factory;
    
    private AdminArea $admin_area;
    
    private UrlGeneratorFactory $generator_factory;
    
    private ?PHPCacheFile $cache_file;
    
    private CachedRouteCollection $cached_routes;
    
    /**
     * @var array<RouteGroup>
     */
    private array $group_stack = [];
    
    private array $fast_route_cache = [];
    
    /**
     * @var array<string,Route>
     */
    private array $_routes = [];
    
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
            Assert::keyExists($cache, 'route_collection');
            Assert::keyExists($cache, 'fast_route');
            $this->fast_route_cache = $cache['fast_route'];
            $this->cached_routes = new CachedRouteCollection($cache['route_collection']);
        }
        
        $this->generator_factory = $generator_factory;
    }
    
    /**
     * @interal
     */
    public function createInGroup(RoutingConfigurator $routing_configurator, Closure $create_routes, array $attributes) :void
    {
        $this->updateGroupStack(new RouteGroup($attributes));
        
        $create_routes($routing_configurator);
        
        $this->deleteCurrentGroup();
    }
    
    /**
     * @interal
     *
     * @param  array<string,string>|string  $controller
     */
    public function registerAdminRoute(string $name, string $path, $controller = Route::DELEGATE) :Route
    {
        $route = $this->createRoute($name, $path, ['GET'], $controller);
        $route->condition(IsAdminDashboardRequest::class);
        return $route;
    }
    
    /**
     * @interal
     *
     * @param  array<string,string>|string  $controller
     */
    public function registerWebRoute(string $name, string $path, array $methods, $controller) :Route
    {
        return $this->createRoute($name, $path, $methods, $controller);
    }
    
    public function dispatch(Request $request) :RoutingResult
    {
        $data = $this->getFastRouteData();
        
        if ($this->cache_file && ! $this->cache_file->isCreated()) {
            $this->createCache($data);
        }
        
        $request = $this->allowMatchingAdminDashboardRequests($request);
        
        return (new FastRouteDispatcher(
            $this->getRoutes(), $data, $this->condition_factory
        ))->dispatch($request);
    }
    
    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string
    {
        return $this->getGenerator()->to($path, $extra, $type, $secure);
    }
    
    public function toRoute(string $name, array $arguments = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string
    {
        return $this->getGenerator()->toRoute($name, $arguments, $type, $secure);
    }
    
    public function secure(string $path, array $extra = []) :string
    {
        return $this->getGenerator()->secure($path, $extra);
    }
    
    public function canonical() :string
    {
        return $this->getGenerator()->canonical();
    }
    
    public function full() :string
    {
        return $this->getGenerator()->full();
    }
    
    public function previous(string $fallback = '/') :string
    {
        return $this->getGenerator()->previous($fallback);
    }
    
    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH) :string
    {
        return $this->getGenerator()->toLogin($arguments, $type);
    }
    
    public function getIterator()
    {
        return $this->getRoutes()->getIterator();
    }
    
    public function count() :int
    {
        return count($this->getRoutes());
    }
    
    public function getByName(string $name) :Route
    {
        return $this->getRoutes()->getByName($name);
    }
    
    public function toArray() :array
    {
        return $this->getRoutes()->toArray();
    }
    
    private function createRoute(string $name, string $path, array $methods, $controller) :Route
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
    
    private function applyGroupPrefix(UrlPath $path) :UrlPath
    {
        if ( ! $this->hasGroup()) {
            return $path;
        }
        
        return $path->prepend($this->currentGroup()->prefix());
    }
    
    private function applyGroupName(string $route_name) :string
    {
        if ( ! $this->hasGroup()) {
            return $route_name;
        }
        
        $g = trim($this->currentGroup()->name(), '.');
        
        if ($g === '') {
            return $route_name;
        }
        
        return "$g.$route_name";
    }
    
    private function applyGroupNamespace() :string
    {
        if ( ! $this->hasGroup()) {
            return '';
        }
        
        return $this->currentGroup()->namespace();
    }
    
    private function hasGroup() :bool
    {
        return count($this->group_stack) > 0;
    }
    
    private function currentGroup() :RouteGroup
    {
        return array_reverse($this->group_stack)[0];
    }
    
    private function addGroupAttributes(Route $route) :void
    {
        if ( ! $this->hasGroup()) {
            return;
        }
        
        foreach ($this->currentGroup()->middleware() as $middleware) {
            $route->middleware($middleware);
        }
    }
    
    private function updateGroupStack(RouteGroup $group) :void
    {
        if ($this->hasGroup()) {
            $group = $group->mergeWith($this->currentGroup());
        }
        
        $this->group_stack[] = $group;
    }
    
    private function deleteCurrentGroup() :void
    {
        array_pop($this->group_stack);
    }
    
    private function getFastRouteData() :array
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
    
    private function createCache(array $fast_route_data) :void
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
            $this->cache_file->realpath(),
            '<?php return '.var_export($arr, true).';'
        );
        
        if ($res === false) {
            throw new RuntimeException("Could not write route cache file.");
        }
    }
    
    private function getGenerator() :InternalUrlGenerator
    {
        if ( ! isset($this->generator)) {
            $this->generator = $this->generator_factory->create($this->getRoutes());
        }
        
        return $this->generator;
    }
    
    private function getRoutes() :Routes
    {
        return $this->cached_routes ?? new RouteCollection($this->_routes);
    }
    
    private function allowMatchingAdminDashboardRequests(Request $request) :Request
    {
        if ( ! $request->isGet()) {
            return $request;
        }
        
        if ( ! $request->isToAdminArea()) {
            return $request;
        }
        
        $uri = $request->getUri();
        $new_uri = $uri->withPath($this->admin_area->rewriteForRouting($request));
        
        return $request->withUri($new_uri);
    }
    
}


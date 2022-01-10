<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Closure;
use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use Snicco\Core\Support\Url;
use FastRoute\RouteCollector;
use Snicco\Core\Support\Path;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\Routes;
use FastRoute\BadRouteException;
use Snicco\Core\Routing\MenuItem;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Support\CacheFile;
use Snicco\Core\Routing\UrlMatcher;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Routing\RoutingResult;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Routing\Exceptions\BadRoute;
use FastRoute\RouteParser\Std as RouteParser;
use Snicco\Core\Routing\RoutingConfigurator;
use Snicco\Core\Routing\Internal\FastRoute\FastRouteSyntax;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use Snicco\Core\Routing\Internal\FastRoute\FastRouteDispatcher;

use function trim;
use function count;
use function serialize;
use function array_pop;
use function var_export;
use function array_reverse;
use function file_put_contents;

/**
 * @interal The Router implements and delegates all core parts of the Routing system.
 */
final class Router implements UrlMatcher, UrlGenerator, RoutingConfigurator
{
    
    private Routes $routes;
    
    private array $config;
    
    private RouteConditionFactory $condition_factory;
    
    private AdminDashboard $admin_dashboard;
    
    private RequestContext $context;
    
    private ?CacheFile $cache_file;
    
    private Generator $generator;
    
    private RoutingConfiguratorUsingRouter $route_configurator;
    
    private array $fast_route_cache = [];
    
    /**
     * @var array<RouteGroup>
     */
    private array $group_stack = [];
    
    public function __construct(
        RouteConditionFactory $condition_factory,
        RequestContext $context,
        array $config,
        CacheFile $cache_file = null
    ) {
        $this->routes = new RouteCollection();
        $this->context = $context;
        $this->config = $config;
        $this->condition_factory = $condition_factory;
        $this->admin_dashboard = $context->adminDashboard();
        $this->cache_file = $cache_file;
        
        if ($this->cache_file && $this->cache_file->isCreated()) {
            $cache = require($this->cache_file->asString());
            Assert::keyExists($cache, 'route_collection');
            Assert::keyExists($cache, 'fast_route');
            $this->fast_route_cache = $cache['fast_route'];
            $this->routes = new CachedRouteCollection($cache['route_collection']);
        }
    }
    
    public function createInGroup(Closure $create_routes, array $attributes) :void
    {
        $this->updateGroupStack(new RouteGroup($attributes));
        
        $create_routes($this->getRouteConfigurator());
        
        $this->deleteCurrentGroup();
    }
    
    public function registerAdminRoute(string $name, string $path, $action = Route::DELEGATE, MenuItem $menu_item = null) :Route
    {
        if ($this->hasGroup() && $this->currentGroup()->prefix()->asString() !== '/') {
            throw new LogicException(
                "Its not possible to add a prefix to admin route [$name]."
            );
        }
        
        $path = Url::combineRelativePath($this->admin_dashboard->urlPrefix(), $path);
        $route = $this->registerRoute($name, $path, ['GET'], $action);
        
        $route->condition(AdminDashboardRequest::class);
        
        return $route;
    }
    
    /**
     * @param  array<string,string>|string  $controller
     */
    public function registerRoute(string $name, string $path, array $methods, $controller) :Route
    {
        // Quick check to see if the developer swapped the arguments by accident.
        Assert::notStartsWith($name, '/');
        
        $path = $this->applyGroupPrefix(Path::fromString($path));
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
        
        $this->routes->add($route);
        
        return $route;
    }
    
    public function dispatch(Request $request) :RoutingResult
    {
        $data = $this->getFastRouteData();
        
        if ($this->cache_file && ! $this->cache_file->isCreated()) {
            $this->createCache($data);
        }
        
        return (new FastRouteDispatcher(
            $this->routes, $data, $this->condition_factory
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
    
    public function fallback($fallback_action) :Route
    {
        return $this->getRouteConfigurator()->fallback($fallback_action);
    }
    
    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []) :Route
    {
        return $this->getRouteConfigurator()->view($path, $view, $data, $status, $headers);
    }
    
    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []) :Route
    {
        return $this->getRouteConfigurator()->redirect($from_path, $to_path, $status, $query);
    }
    
    public function permanentRedirect(string $from_path, string $to_path, array $query = []) :Route
    {
        return $this->getRouteConfigurator()->permanentRedirect($from_path, $to_path, $query);
    }
    
    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307) :Route
    {
        return $this->getRouteConfigurator()->temporaryRedirect(
            $from_path,
            $to_path,
            $query,
            $status
        );
    }
    
    public function redirectAway(string $from_path, string $location, int $status = 302) :Route
    {
        return $this->getRouteConfigurator()->redirectAway(
            $from_path,
            $location,
            $status,
        );
    }
    
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302) :Route
    {
        return $this->getRouteConfigurator()->redirectToRoute(
            $from_path,
            $route,
            $arguments,
            $status
        );
    }
    
    public function group(Closure $create_routes, array $extra_attributes = []) :void
    {
        $this->getRouteConfigurator()->group($create_routes, $extra_attributes);
    }
    
    public function get(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->get($name, $path, $action);
    }
    
    public function post(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->post($name, $path, $action);
    }
    
    public function put(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->put($name, $path, $action);
    }
    
    public function patch(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->patch($name, $path, $action);
    }
    
    public function delete(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->delete($name, $path, $action);
    }
    
    public function options(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->options($name, $path, $action);
    }
    
    public function any(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->any($name, $path, $action);
    }
    
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->getRouteConfigurator()->match($verbs, $name, $path, $action);
    }
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, MenuItem $menu_item = null) :Route
    {
        return $this->getRouteConfigurator()->admin($name, $path, $action, $menu_item);
    }
    
    public function middleware($middleware) :RoutingConfigurator
    {
        return $this->getRouteConfigurator()->middleware($middleware);
    }
    
    public function name(string $name) :RoutingConfigurator
    {
        return $this->getRouteConfigurator()->name($name);
    }
    
    public function prefix(string $prefix) :RoutingConfigurator
    {
        return $this->getRouteConfigurator()->prefix($prefix);
    }
    
    public function namespace(string $namespace) :RoutingConfigurator
    {
        return $this->getRouteConfigurator()->namespace($namespace);
    }
    
    public function configValue(string $key)
    {
        return $this->getRouteConfigurator()->configValue($key);
    }
    
    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH) :string
    {
        return $this->getGenerator()->toLogin($arguments, $type);
    }
    
    private function applyGroupPrefix(Path $path) :Path
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
        $syntax = new FastRouteSyntax();
        
        foreach ($this->routes as $route) {
            $path = $syntax->convert($route);
            try {
                $collector->addRoute($route->getMethods(), $path, $route->getName());
            } catch (BadRouteException $e) {
                throw BadRoute::fromPrevious($e);
            }
        }
        
        return $collector->getData();
    }
    
    private function createCache(array $fast_route_data) :void
    {
        $_r = [];
        
        foreach ($this->routes as $name => $route) {
            $_r[$name] = serialize($route);
        }
        
        $arr = [
            'route_collection' => $_r,
            'fast_route' => $fast_route_data,
        ];
        $res = file_put_contents(
            $this->cache_file->asString(),
            '<?php return '.var_export($arr, true).';'
        );
        
        if ($res === false) {
            throw new RuntimeException("Could not write route cache file.");
        }
    }
    
    private function getGenerator() :Generator
    {
        if ( ! isset($this->generator)) {
            $this->generator = new Generator(
                $this->routes,
                $this->context,
                new RFC3986Encoder()
            );
        }
        
        return $this->generator;
    }
    
    private function getRouteConfigurator() :RoutingConfiguratorUsingRouter
    {
        if ( ! isset($this->route_configurator)) {
            $this->route_configurator = new RoutingConfiguratorUsingRouter($this, $this->config);
        }
        
        return $this->route_configurator;
    }
    
}


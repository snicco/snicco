<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\RoutingConfigurator;

use Closure;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use Snicco\Core\Routing\Router;
use Snicco\Core\Support\UrlPath;
use Snicco\Core\Routing\Route\Route;
use Snicco\Core\Controllers\ViewController;
use Snicco\Core\Routing\Exception\BadRoute;
use Snicco\Core\Controllers\RedirectController;
use Snicco\Core\Routing\AdminDashboard\AdminMenu;
use Snicco\Core\Routing\AdminDashboard\AdminMenuItem;
use Snicco\Core\Routing\Condition\AbstractRouteCondition;
use Snicco\Core\Routing\Condition\IsAdminDashboardRequest;
use Snicco\Core\Routing\AdminDashboard\AdminDashboardPrefix;

use function array_map;

/**
 * @interal
 */
final class RoutingConfiguratorUsingRouter implements WebRoutingConfigurator, AdminRoutingConfigurator
{
    
    private Router $router;
    private AdminDashboardPrefix $admin_dashboard_prefix;
    private array $delegate_attributes = [];
    private array $config;
    private bool $locked               = false;
    
    /**
     * @var AdminMenuItem[]
     */
    private array $admin_menu_items = [];
    
    public function __construct(Router $router, AdminDashboardPrefix $admin_dashboard_prefix, array $config)
    {
        $this->admin_dashboard_prefix = $admin_dashboard_prefix;
        $this->router = $router;
        $this->config = $config;
    }
    
    public function get(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['GET', 'HEAD'], $action);
    }
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, AdminMenuItem $menu_item = null) :Route
    {
        $this->checkDelegatedAttributesAreEmpty($name);
        
        $prefix = $this->admin_dashboard_prefix->asString();
        if (UrlPath::fromString($path)->startsWith($prefix)) {
            throw BadRoute::becauseAdminRouteWasAddedWithHardcodedPrefix($name, $prefix);
        }
        
        $path = $this->admin_dashboard_prefix->appendPath($path);
        $route = $this->router->registerAdminRoute($name, $path, $action);
        
        if (count($route->getSegmentNames())) {
            throw BadRoute::becauseAdminRouteHasSegments($route->getName());
        }
        
        if (Route::DELEGATE !== $action) {
            $this->addMenuItem($route, $menu_item);
        }
        
        return $route;
    }
    
    public function post(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['POST'], $action);
    }
    
    public function put(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['PUT'], $action);
    }
    
    public function patch(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['PATCH'], $action);
    }
    
    public function delete(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['DELETE'], $action);
    }
    
    public function options(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, ['OPTIONS'], $action);
    }
    
    public function any(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, Route::ALL_METHODS, $action);
    }
    
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addWebRoute($name, $path, array_map('strtoupper', $verbs), $action);
    }
    
    /**
     * @param  string|array<string>  $middleware
     */
    public function middleware($middleware) :self
    {
        $this->delegate_attributes[RoutingConfigurator::MIDDLEWARE_KEY] = Arr::wrap($middleware);
        return $this;
    }
    
    public function name(string $name) :self
    {
        $this->delegate_attributes[RoutingConfigurator::NAME_KEY] = $name;
        return $this;
    }
    
    public function prefix(string $prefix) :self
    {
        $this->delegate_attributes[RoutingConfigurator::PREFIX_KEY] = UrlPath::fromString($prefix);
        return $this;
    }
    
    public function namespace(string $namespace) :self
    {
        $this->delegate_attributes[RoutingConfigurator::NAMESPACE_KEY] = $namespace;
        return $this;
    }
    
    public function fallback(
        $fallback_action, array $dont_match_request_including = [
        'favicon',
        'robots',
        'sitemap',
    ]
    ) :Route {
        Assert::allString(
            $dont_match_request_including,
            'All fallback excludes have to be strings.'
        );
        
        $dont_match_request_including[] = trim($this->admin_dashboard_prefix->asString(), '/');
        
        $regex = sprintf('(?!%s).+', implode('|', $dont_match_request_including));
        
        $route = $this->any(Route::FALLBACK_NAME, '/{path}', $fallback_action)
                      ->requirements(['path' => $regex])
                      ->condition(AbstractRouteCondition::NEGATE, IsAdminDashboardRequest::class);
        
        $this->locked = true;
        
        return $route;
    }
    
    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []) :Route
    {
        $name = 'view:'.Str::afterLast($view, '/');
        
        $route = $this->match(['GET', 'HEAD'], $name, $path, [ViewController::class, 'handle']);
        $route->defaults([
            'view' => $view,
            'data' => $data,
            'status' => $status,
            'headers' => $headers,
        ]);
        
        return $route;
    }
    
    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []) :Route
    {
        $route = $this->any(
            $this->redirectRouteName($from_path, $to_path),
            $from_path,
            [RedirectController::class, 'to']
        );
        $route->defaults([
            'to' => $to_path,
            'status' => $status,
            'query' => $query,
        ]);
        
        return $route;
    }
    
    public function permanentRedirect(string $from_path, string $to_path, array $query = []) :Route
    {
        return $this->redirect($from_path, $to_path, 301, $query);
    }
    
    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307) :Route
    {
        return $this->redirect($from_path, $to_path, $status, $query);
    }
    
    public function redirectAway(string $from_path, string $location, int $status = 302) :Route
    {
        $name = $this->redirectRouteName($from_path, $location);
        return $this->any($name, $from_path, [RedirectController::class, 'away'])->defaults([
            'location' => $location,
            'status' => $status,
        ]);
    }
    
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302) :Route
    {
        $name = $this->redirectRouteName($from_path, $route);
        return $this->any($name, $from_path, [RedirectController::class, 'toRoute'])->defaults([
            'route' => $route,
            'arguments' => $arguments,
            'status' => $status,
        ]);
    }
    
    public function group(Closure $create_routes, array $extra_attributes = []) :void
    {
        $attributes = Arr::mergeRecursive($this->delegate_attributes, $extra_attributes);
        $this->delegate_attributes = [];
        $this->router->createInGroup(
            $this,
            $create_routes,
            $attributes
        );
    }
    
    public function configValue(string $key)
    {
        Assert::keyExists($this->config, $key);
        return $this->config[$key];
    }
    
    public function include($file_or_closure) :void
    {
        $routes = $file_or_closure;
        if ( ! $routes instanceof Closure) {
            Assert::string($file_or_closure, '$file_or_closure has to be a string or a closure.');
            Assert::readable($file_or_closure, "The file $file_or_closure is not readable.");
            
            Assert::isInstanceOf(
                $routes = require $file_or_closure,
                Closure::class,
                sprintf(
                    "Requiring the file [%s] has to return a closure.\nGot: [%s]",
                    $file_or_closure,
                    gettype($file_or_closure)
                )
            );
        }
        
        $this->group($routes);
    }
    
    public function configureAdminMenu(AdminMenu $menu) :void
    {
        foreach ($this->admin_menu_items as $admin_menu_item) {
            $menu->add($admin_menu_item);
        }
    }
    
    private function redirectRouteName(string $from, string $to) :string
    {
        return "redirect_route:$from:$to";
    }
    
    private function addWebRoute(string $name, string $path, array $methods, $action) :Route
    {
        $this->checkDelegatedAttributesAreEmpty($name);
        
        if ($this->locked) {
            throw BadRoute::becauseFallbackRouteIsAlreadyRegistered($name);
        }
        
        if (UrlPath::fromString($path)->startsWith($this->admin_dashboard_prefix->asString())) {
            throw BadRoute::becauseWebRouteHasAdminPrefix($name);
        }
        
        return $this->router->registerWebRoute($name, $path, $methods, $action);
    }
    
    private function checkDelegatedAttributesAreEmpty(string $route_name) :void
    {
        if ( ! empty($this->delegate_attributes)) {
            throw BadRoute::becauseDelegatedAttributesHaveNotBeenGrouped($route_name);
        }
    }
    
    private function addMenuItem(Route $route, ?AdminMenuItem $menu_item = null)
    {
        if ($menu_item) {
            $this->admin_menu_items[] = $menu_item;
        }
        
        $this->admin_menu_items[] = AdminMenuItem::fromRoute($route);
    }
    
}
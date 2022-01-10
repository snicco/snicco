<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Closure;
use RuntimeException;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use Snicco\Core\Support\Path;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\MenuItem;
use Snicco\Core\Controllers\ViewController;
use Snicco\Core\Routing\RoutingConfigurator;
use Snicco\Core\Controllers\RedirectController;

/**
 * @interal
 */
final class RoutingConfiguratorUsingRouter implements RoutingConfigurator
{
    
    private Router $router;
    private array  $delegate_attributes = [];
    private array  $config;
    
    public function __construct(Router $router, array $config)
    {
        $this->router = $router;
        $this->config = $config;
    }
    
    public function get(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['GET', 'HEAD'], $action);
    }
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, MenuItem $menu_item = null) :Route
    {
        return $this->router->registerAdminRoute($name, $path, $action, $menu_item);
    }
    
    public function post(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['POST'], $action);
    }
    
    public function put(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['PUT'], $action);
    }
    
    public function patch(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['PATCH'], $action);
    }
    
    public function delete(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['DELETE'], $action);
    }
    
    public function options(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, ['OPTIONS'], $action);
    }
    
    public function any(string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, Route::ALL_METHODS, $action);
    }
    
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE) :Route
    {
        return $this->addRoute($name, $path, array_map('strtoupper', $verbs), $action);
    }
    
    /**
     * @param  string|array<string>  $middleware
     */
    public function middleware($middleware) :self
    {
        $this->delegate_attributes['middleware'] = Arr::wrap($middleware);
        return $this;
    }
    
    public function name(string $name) :self
    {
        $this->delegate_attributes['name'] = $name;
        return $this;
    }
    
    public function prefix(string $prefix) :self
    {
        $this->delegate_attributes['prefix'] = Path::fromString($prefix);
        return $this;
    }
    
    public function namespace(string $namespace) :self
    {
        $this->delegate_attributes['namespace'] = $namespace;
        return $this;
    }
    
    public function fallback($fallback_action) :Route
    {
        return $this->any(Route::FALLBACK_NAME, '/{path}', $fallback_action)
                    ->middleware('web')->requirements(['path' => '.+']);
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
            $create_routes,
            $attributes
        );
    }
    
    public function configValue(string $key)
    {
        Assert::keyExists($this->config, $key);
        return $this->config[$key];
    }
    
    private function redirectRouteName(string $from, string $to) :string
    {
        return "redirect_route:$from:$to";
    }
    
    private function addRoute(string $name, string $path, array $methods, $action) :Route
    {
        if ( ! empty($this->delegate_attributes)) {
            throw new RuntimeException(
                'Delegated attributes have not been merged into a route group. Did you forget to call [group]?'
            );
        }
        
        return $this->router->registerRoute($name, $path, $methods, $action);
    }
    
}
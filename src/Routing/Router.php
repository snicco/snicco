<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Closure;
use Snicco\Support\WP;
use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Support\Arr;
use Snicco\Controllers\ViewController;
use Snicco\Traits\HoldsRouteBlueprint;
use Snicco\Controllers\RedirectController;
use Snicco\Contracts\RouteCollectionInterface;

class Router
{
    
    use HoldsRouteBlueprint;
    
    /** @var RouteGroup[] */
    private array $group_stack = [];
    
    private RouteCollectionInterface $routes;
    private bool                     $force_trailing;
    
    public function __construct(RouteCollectionInterface $routes, bool $force_trailing = false)
    {
        $this->routes = $routes;
        $this->force_trailing = $force_trailing;
    }
    
    public function view(string $url, string $view, array $data = [], int $status = 200, array $headers = []) :Route
    {
        $route = $this->match(['GET', 'HEAD'], $url, ViewController::class.'@handle');
        $route->defaults([
            'view' => $view,
            'data' => $data,
            'status' => $status,
            'headers' => $headers,
        ]);
        
        return $route;
    }
    
    public function permanentRedirect(string $url, string $location, bool $secure = true, bool $absolute = false) :Route
    {
        return $this->redirect($url, $location, 301, $secure, $absolute);
    }
    
    public function redirect(string $url, string $location, int $status = 302, bool $secure = true, bool $absolute = false) :Route
    {
        return $this->any($url, [RedirectController::class, 'to'])->defaults([
            'location' => $location,
            'status' => $status,
            'secure' => $secure,
            'absolute' => $absolute,
        ]);
    }
    
    public function temporaryRedirect(string $url, string $location, bool $secure = true, bool $absolute = false) :Route
    {
        return $this->redirect($url, $location, 307, $secure, $absolute);
    }
    
    public function redirectAway(string $url, string $location, int $status = 302) :Route
    {
        return $this->any($url, [RedirectController::class, 'away'])->defaults([
            'location' => $location,
            'status' => $status,
        ]);
    }
    
    public function redirectToRoute(string $url, string $route, array $params = [], int $status = 302) :Route
    {
        return $this->any($url, [RedirectController::class, 'toRoute'])->defaults([
            'route' => $route,
            'status' => $status,
            'params' => $params,
        ]);
    }
    
    public function addRoute(array $methods, string $path, $action = null) :Route
    {
        $url = $this->applyPrefix($path);
        
        $url = $this->formatTrailing($url);
        
        $route = $this->newRoute($url, $methods, $action);
        
        if ($this->force_trailing && ! Url::isFileURL($route->getUrl())) {
            $route->andOnlyTrailing();
        }
        
        if ($this->hasGroupStack()) {
            $this->mergeGroupIntoRoute($route);
        }
        
        if ( ! empty($this->delegate_attributes)) {
            $this->populateInitialAttributes($route, $this->delegate_attributes);
        }
        
        return $this->routes->add($route);
    }
    
    public function group(Closure $routes, array $attributes = [])
    {
        $attributes = Arr::mergeRecursive($this->delegate_attributes, $attributes);
        $this->delegate_attributes = [];
        
        $this->updateGroupStack(new RouteGroup($attributes));
        
        $this->registerRoutes($routes);
        
        $this->deleteLastRouteGroup();
    }
    
    /**
     * @internal
     */
    public function loadRoutes()
    {
        $this->routes->addToUrlMatcher();
    }
    
    public function fallback($fallback_action) :Route
    {
        $pattern = $this->force_trailing
            ? '(?!\/'.WP::wpAdminFolder().')[^.\s]+?\/'
            : '(?!\/'.WP::wpAdminFolder().')[^.\s]+?[^\/]';
        
        return $this->any('/{'.Route::ROUTE_FALLBACK_NAME.'}', $fallback_action)
                    ->middleware('web')
                    ->and(Route::ROUTE_FALLBACK_NAME, $pattern)->fallback();
    }
    
    private function applyPrefix(string $url) :string
    {
        if ( ! $this->hasGroupStack()) {
            return $url;
        }
        
        $url = $this->maybeStripTrailing($url);
        
        return Url::combineRelativePath($this->lastGroupPrefix(), $url);
    }
    
    private function hasGroupStack() :bool
    {
        return ! empty($this->group_stack);
    }
    
    private function maybeStripTrailing(string $url) :string
    {
        if (trim($this->lastGroupPrefix(), '/') === WP::wpAdminFolder()) {
            return rtrim($url, '/');
        }
        
        if (trim($this->lastGroupPrefix(), '/') === WP::ajaxUrl()) {
            return rtrim($url, '/');
        }
        
        return $url;
    }
    
    private function lastGroupPrefix() :string
    {
        if ( ! $this->hasGroupStack()) {
            return '';
        }
        
        return $this->lastGroup()->prefix();
    }
    
    private function lastGroup()
    {
        return end($this->group_stack);
    }
    
    private function formatTrailing(string $url) :string
    {
        $admin_dir = WP::wpAdminFolder();
        
        // always ensure exact trailing slash for /wp-admin/ as WordPress will always redirect
        // /wp-admin => /wp-admin/
        if (trim($url, '/') === $admin_dir) {
            return '/'.Url::addTrailing($url);
        }
        
        if ( ! $this->force_trailing) {
            return Url::removeTrailing($url);
        }
        
        // Never add a trailing slash the fallback route nor any route that
        // goes to a file on the filesystem. (mostly wp-admin/*)
        if (Str::contains($url, Route::ROUTE_FALLBACK_NAME)
            || Str::contains($url, '.php')
            || Str::contains($url, $admin_dir)) {
            return Url::removeTrailing($url);
        }
        
        return Url::addTrailing($url);
    }
    
    private function mergeGroupIntoRoute(Route $route)
    {
        $this->lastGroup()->mergeIntoRoute($route);
    }
    
    private function populateInitialAttributes(Route $route, array $attributes)
    {
        (new RouteGroup($attributes))->mergeIntoRoute($route);
        $this->delegate_attributes = [];
    }
    
    private function updateGroupStack(RouteGroup $group)
    {
        if ($this->hasGroupStack()) {
            $group = $this->mergeWithLastGroup($group);
        }
        
        $this->group_stack[] = $group;
    }
    
    private function mergeWithLastGroup(RouteGroup $new_group) :RouteGroup
    {
        return $new_group->mergeWith($this->lastGroup());
    }
    
    private function registerRoutes(Closure $routes)
    {
        $routes($this);
    }
    
    private function deleteLastRouteGroup()
    {
        array_pop($this->group_stack);
    }
    
    private function newRoute(string $url, array $methods, $action) :Route
    {
        if (preg_match('/\/?'.WP::wpAdminFolder().'\/admin-ajax\.php\/.+/', $url)) {
            return new AjaxRoute($methods, $url, $action);
        }
        
        if (preg_match('/\/?'.WP::wpAdminFolder().'\/.+\.php\/.+/', $url)) {
            return new AdminRoute($methods, $url, $action);
        }
        
        return new Route($methods, $url, $action);
    }
    
}


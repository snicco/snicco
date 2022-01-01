<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Closure;
use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Core\Support\WP;
use Snicco\Core\Support\Url;
use Snicco\Core\Traits\HoldsRouteBlueprint;
use Snicco\Core\Controllers\ViewAbstractController;
use Snicco\Core\Contracts\RouteCollectionInterface;
use Snicco\Core\Controllers\RedirectAbstractController;

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
        $route = $this->match(['GET', 'HEAD'], $url, ViewAbstractController::class.'@handle');
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
        return $this->any($from_path, [RedirectAbstractController::class, 'to'])->defaults([
            'to' => $to_path,
            'status' => $status,
            'query' => $query,
        ]);
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
        return $this->any($from_path, [RedirectAbstractController::class, 'away'])->defaults([
            'location' => $location,
            'status' => $status,
        ]);
    }
    
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302) :Route
    {
        return $this->any($from_path, [RedirectAbstractController::class, 'toRoute'])->defaults([
            'route' => $route,
            'arguments' => $arguments,
            'status' => $status,
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


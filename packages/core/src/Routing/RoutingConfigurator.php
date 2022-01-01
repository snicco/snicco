<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Closure;
use RuntimeException;

/**
 * @api
 */
interface RoutingConfigurator
{
    
    public function get(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function post(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function put(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function patch(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function delete(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function options(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function any(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, MenuItem $menu_item = null) :Route;
    
    /**
     * @param  array<string,string>|string  $fallback_action
     */
    public function fallback($fallback_action) :Route;
    
    /**
     * @param  string  $view
     * The template identifier. Can be an absolute path or if supported, just the file name.
     *
     * @see TemplateRenderer::render()
     */
    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []) :Route;
    
    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []) :Route;
    
    public function permanentRedirect(string $from_path, string $to_path, array $query = []) :Route;
    
    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307) :Route;
    
    public function redirectAway(string $from_path, string $location, int $status = 302) :Route;
    
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302) :Route;
    
    /**
     * @param  string|array  $middleware
     */
    public function middleware($middleware) :self;
    
    public function name(string $name) :self;
    
    public function prefix(string $prefix) :self;
    
    public function namespace(string $namespace) :self;
    
    public function group(Closure $create_routes, array $extra_attributes = []) :void;
    
    /**
     * Retrieve a configuration value.
     *
     * @return mixed
     * @throws RuntimeException If the key does not exist
     */
    public function configValue(string $key);
    
}
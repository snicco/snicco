<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\RoutingConfigurator;

use Closure;
use InvalidArgumentException;
use Snicco\Core\Routing\Route\Route;

interface RoutingConfigurator
{
    
    const MIDDLEWARE_KEY = 'middleware';
    const PREFIX_KEY = 'prefix';
    const NAMESPACE_KEY = 'namespace';
    const NAME_KEY = 'name';
    const WEB_MIDDLEWARE = 'web';
    const ADMIN_MIDDLEWARE = 'admin';
    const GLOBAL_MIDDLEWARE = 'global';
    
    /**
     * @param  string|array  $middleware
     */
    public function middleware($middleware) :self;
    
    public function name(string $name) :self;
    
    public function namespace(string $namespace) :self;
    
    public function group(Closure $create_routes, array $extra_attributes = []) :void;
    
    /**
     * Retrieves a configuration value.
     *
     * @return mixed
     * @throws InvalidArgumentException If the key does not exist.
     */
    public function configValue(string $key);
    
    /**
     * @param  string|Closure  $file_or_closure  Either the full path to route file that will
     *      return a closure or the closure itself
     */
    public function include($file_or_closure) :void;
    
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
    
}
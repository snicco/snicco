<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use InvalidArgumentException;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\Route\Route;

/**
 * @api
 */
interface RoutingConfigurator
{

    const MIDDLEWARE_KEY = 'middleware';
    const PREFIX_KEY = 'prefix';
    const NAMESPACE_KEY = 'namespace';
    const NAME_KEY = 'name';
    const FRONTEND_MIDDLEWARE = 'frontend';
    const API_MIDDLEWARE = 'api';
    const ADMIN_MIDDLEWARE = 'admin';
    const GLOBAL_MIDDLEWARE = 'global';

    /**
     * @param string|string[] $middleware
     */
    public function middleware($middleware): self;

    public function name(string $name): self;

    public function namespace(string $namespace): self;

    /**
     * @param Closure($this):void $create_routes
     *
     * @param array{
     *     namespace?:string,
     *     prefix?:string,
     *     name?:string,
     *     middleware?: string[]
     * } $extra_attributes
     */
    public function group(Closure $create_routes, array $extra_attributes = []): void;

    /**
     * Retrieves a configuration value.
     *
     * @return mixed
     * @throws InvalidArgumentException If the key does not exist.
     */
    public function configValue(string $key);

    /**
     * @param string|Closure($this):void $file_or_closure Either the full path to route file that will
     *                                                  return a closure or the closure itself
     */
    public function include($file_or_closure): void;

    /**
     * @param string $view
     * The template identifier. Can be an absolute path or if supported, just the file name.
     *
     * @param array<string,scalar> $data
     * @param array<string,string> $headers
     *
     * @see TemplateRenderer::render()
     */
    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []): Route;

    /**
     * @param array<string,string|int> $query
     */
    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []): Route;

    /**
     * @param array<string,string|int> $query
     */
    public function permanentRedirect(string $from_path, string $to_path, array $query = []): Route;

    /**
     * @param array<string,string|int> $query
     */
    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307): Route;

    public function redirectAway(string $from_path, string $location, int $status = 302): Route;

    /**
     * @param array<string,string|int> $arguments
     */
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302): Route;

}
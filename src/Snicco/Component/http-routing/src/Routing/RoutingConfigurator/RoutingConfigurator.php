<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use Snicco\Component\HttpRouting\Routing\Route\Routes;

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
     * @return static
     */
    public function middleware($middleware);

    /**
     * @param string $name
     * @return static
     */
    public function name(string $name);

    /**
     * @param string $namespace
     * @return static
     */
    public function namespace(string $namespace);

    /**
     * @param Closure(static) $create_routes
     *
     * @param array{
     *     namespace?:string,
     *     prefix?:string,
     *     name?:string,
     *     middleware?: string[]
     * } $extra_attributes
     *
     */
    public function group(Closure $create_routes, array $extra_attributes = []): void;

    /**
     * @param string|Closure(static) $file_or_closure Either the full path to route file that will
     *                                                  return a closure or the closure itself
     */
    public function include($file_or_closure): void;

    public function configuredRoutes(): Routes;

}
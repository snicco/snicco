<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use Snicco\Component\HttpRouting\Routing\Route\Routes;

interface RoutingConfigurator
{
    public const MIDDLEWARE_KEY = 'middleware';

    public const PREFIX_KEY = 'prefix';

    public const NAMESPACE_KEY = 'namespace';

    public const NAME_KEY = 'name';

    public const FRONTEND_MIDDLEWARE = 'frontend';

    public const API_MIDDLEWARE = 'api';

    public const ADMIN_MIDDLEWARE = 'admin';

    public const GLOBAL_MIDDLEWARE = 'global';

    /**
     * @param string|string[] $middleware
     * @return static
     */
    public function middleware($middleware);

    /**
     * @return static
     */
    public function name(string $name);

    /**
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
     */
    public function group(Closure $create_routes, array $extra_attributes = []): void;

    /**
     * @param string|Closure(static) $file_or_closure Either the full path to route file that will
     *                                                  return a closure or the closure itself
     */
    public function include($file_or_closure): void;

    public function configuredRoutes(): Routes;
}

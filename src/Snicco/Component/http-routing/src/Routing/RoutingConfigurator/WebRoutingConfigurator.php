<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;

/**
 * The RoutingConfigurator can be used to fluently register Routes with the routing component.
 *
 * @api
 * @note If any combination of the methods ['prefix', 'middleware', 'name', 'namespace'] is used
 *       without a successive call to ['group'] a BadRoute will be thrown.
 *
 */
interface WebRoutingConfigurator extends RoutingConfigurator
{

    public function prefix(string $prefix): self;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function get(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function post(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function put(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function patch(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function delete(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function options(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     */
    public function any(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param class-string|array{0: class-string, 1:string} $action
     * @param string[] $verbs
     */
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * Creates a fallback route that will match ALL web requests. Request to the admin dashboard
     * are never matched.
     * The fallback route has to be the last route that is registered in the
     * application. Attempting to register another route after the fallback route will throw a
     * {@see BadRouteConfiguration}
     *
     * @param class-string|array{0: class-string, 1:string} $fallback_action The fallback controller
     *
     * @param string[] $dont_match_request_including An array of REGEX strings that will be
     *                                               joined to a regular expression which will not match if any
     *                                               string is included in the request path.
     */
    public function fallback(
        $fallback_action,
        array $dont_match_request_including = [
            'favicon',
            'robots',
            'sitemap',
        ]
    ): Route;

}
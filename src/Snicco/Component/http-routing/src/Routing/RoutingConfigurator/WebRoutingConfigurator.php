<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;

/**
 * @note If any combination of the methods ['prefix', 'middleware', 'name', 'namespace'] is used
 *       without a successive call to ['group'] a {@see BadRouteConfiguration} will be thrown.
 */
interface WebRoutingConfigurator extends RoutingConfigurator
{
    /**
     * @return static
     */
    public function prefix(string $prefix);

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function get(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function post(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function put(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function patch(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function delete(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function options(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     */
    public function any(string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * @param array{0: class-string, 1:string}|class-string|string $action
     * @param string[]                                             $verbs
     */
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE): Route;

    /**
     * Creates a fallback route that will match ALL web requests. Request to the
     * admin dashboard are never matched. The fallback route has to be the last
     * route that is registered in the application. Attempting to register
     * another route after the fallback route will throw a.
     *
     * {@see BadRouteConfiguration}.
     *
     * @param array{0: class-string, 1:string}|class-string $fallback_action              The fallback controller
     * @param string[]                                      $dont_match_request_including an array of REGEX strings that will be
     *                                                                                    joined to a regular expression which will not match if any
     *                                                                                    string is included in the request path
     */
    public function fallback(
        $fallback_action,
        array $dont_match_request_including = ['favicon', 'robots', 'sitemap']
    ): Route;

    /**
     * This method will create an internal route that will return an instance
     * of.
     *
     * {@see ViewResponse} Its the users' responsibility when and where this
     * response is transformed into an actual response with content.
     *
     * @param array<string,scalar> $data
     * @param array<string,string> $headers
     *
     * @see ViewResponse
     */
    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []): Route;

    /**
     * @param array<string,int|string> $query
     */
    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []): Route;

    /**
     * @param array<string,int|string> $query
     */
    public function permanentRedirect(string $from_path, string $to_path, array $query = []): Route;

    /**
     * @param array<string,int|string> $query
     */
    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307): Route;

    public function redirectAway(string $from_path, string $location, int $status = 302): Route;

    /**
     * @param array<string,int|string> $arguments
     */
    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302): Route;
}

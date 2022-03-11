<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

interface AdminArea
{
    /**
     * Returns the url prefix that all admin routes share.
     */
    public function urlPrefix(): AdminAreaPrefix;

    /**
     * The path relative to the root domain where users can log in into the
     * admin dashboard.
     */
    public function loginPath(): string;

    /**
     * Has to return an array where the first key is the new url path and the
     * second key is an array of key value pairs where the key is a query
     * argument name and the value is the query argument value (not urlencoded).
     *
     * @return array{0:string, 1: array<string,string>}
     */
    public function rewriteForUrlGeneration(string $route_pattern): array;

    /**
     * If admin request are used in a legacy system that uses query parameters
     * for routing this method will be called to return a path that the @see
     * UrlMatcher can understand The path returned from this method will not be
     * used anywhere but for matching the request.
     */
    public function rewriteForRouting(Request $request): string;
}

<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;

/**
 * This interface represents a simple value object in order to avoid having to pass primitive
 * configuration values.
 *
 * @api
 */
interface AdminDashboard
{
    
    /**
     * Returns the url prefix that all admin routes share.
     */
    public function urlPrefix() :string;
    
    /**
     * The path relative to the root domain where users can login into the admin dashboard.
     *
     * @return string
     */
    public function loginPath() :string;
    
    /**
     * Determine if the current request can be considered to be going to the admin dashboard.
     */
    public function goesTo(Request $request) :bool;
    
    /**
     * Has to return an array where the first key is the new url path and the second
     * key is an array of key value pairs where the key is a query argument name
     * and the value is the query argument value (not urlencoded).
     *
     * @param  string  $route_pattern
     *
     * @return array<string,array<string,string>>
     */
    public function rewriteForUrlGeneration(string $route_pattern) :array;
    
    /**
     * If admin request are used in a legacy system that uses query parameters for routing
     * this method will be called to return a path that the @see UrlMatcher can understand
     * The path returned from this method will not be used anywhere but for matching the request.
     */
    public function rewriteForRouting(Request $request) :string;
    
}
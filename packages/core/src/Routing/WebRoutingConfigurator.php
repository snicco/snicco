<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

/**
 * The RoutingConfigurator can be used to fluently register Routes with the routing component.
 *
 * @note If any combination of the methods ['prefix', 'middleware', 'name', 'namespace'] is used
 *       without a successive call to ['group'] a LogicException will be thrown.
 * @api
 */
interface WebRoutingConfigurator extends RoutingConfigurator
{
    
    public function prefix(string $prefix) :self;
    
    public function get(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function post(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function put(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function patch(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function delete(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function options(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function any(string $name, string $path, $action = Route::DELEGATE) :Route;
    
    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE) :Route;
    
    /**
     * Creates a fallback route that will match ALL requests.
     * The fallback route has to be the last route that is registered in the application.
     * Attempting to register another route after the fallback route will throw a
     * {@see \LogicException}
     *
     * @param  array<string,string>|string  $fallback_action  The fallback controller
     * @param  array<string >  $dont_match_request_including  An array of REGEX strings that will be
     *     joined to a regular expression which will not match if any string is included in the
     *     request path.
     */
    public function fallback(
        $fallback_action, array $dont_match_request_including = [
        'favicon',
        'robots',
        'sitemap',
    ]
    ) :Route;
    
}
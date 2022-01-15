<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Http;

use Snicco\HttpRouting\Http\Responses\RedirectResponse;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

interface Redirector
{
    
    /**
     * Tries to create a redirect response to a "home" route and falls back to "/" if no home route
     * exists.
     */
    public function home(array $arguments = [], int $status_code = 302) :RedirectResponse;
    
    /**
     * @throws RouteNotFound
     * @see UrlGenerator::toRoute()
     */
    public function toRoute(string $name, array $arguments = [], int $status_code = 302) :RedirectResponse;
    
    /**
     * Redirect to the current path+query with a 302 status code.
     */
    public function refresh() :RedirectResponse;
    
    /**
     * Redirects the user the referer header or the fallback path if the header is not present.
     */
    public function back(string $fallback = '/', int $status_code = 302) :RedirectResponse;
    
    /**
     * Redirects the user to the provided path and appends an "intended" query param with a value
     * of the current full url. Meant to be used in combination with
     * {@see Redirector::intended()}
     */
    public function deny(string $path, int $status_code = 302, array $query = []) :RedirectResponse;
    
    /**
     * Looks for an "intended" query param and redirects the user to it.
     * Meant to be used in combination with
     * {@see Redirector::deny()}
     *
     * @param  string  $fallback  If the query parameter is not present
     */
    public function intended(string $fallback = '/', int $status_code = 302) :RedirectResponse;
    
    /**
     * Redirects to a path on the same domain.
     *
     * @see UrlGenerator::to()
     */
    public function to(string $path, int $status_code = 302, array $query = []) :RedirectResponse;
    
    /**
     * Redirects to a path on the same domain. Will force a redirect to https if the current
     * request scheme is http.
     */
    public function secure(string $path, int $status_code = 302, array $query = []) :RedirectResponse;
    
    /**
     * This function SHOULD NEVER be used with user supplied input, or you are exposing yourself to
     * open-redirect exploits.
     */
    public function away(string $absolute_url, int $status_code = 302) :RedirectResponse;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteParameter;

/**
 * @api
 */
interface UrlGenerator
{
    
    /**
     * Generate an absolute URL, e.g. 'https://example.com/foo/bar'.
     */
    const ABSOLUTE_URL = 0;
    
    /**
     * Generate an absolute path, e.g. '/foo/bar'.
     */
    const ABSOLUTE_PATH = 1;
    
    /**
     * @param  string  $path  The path MUST NOT be urlencoded.
     * @param  array<string,string|int>  $extra  The query arguments to append.
     * A "_fragment" key can be passed to include a fragment after the query string.
     * @param  int  $type
     * @param  bool|null  $secure  If null is passed the scheme of the current request will be used.
     *
     * @return string an rfc-compliant url
     */
    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string;
    
    /**
     * @throws RouteNotFound
     * @throws BadRouteParameter
     */
    public function toRoute(string $name, array $arguments = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string;
    
    /**
     * Tries to redirect to the routes in the following order:
     * 'login'
     * 'auth.login'
     * 'framework.auth.login'
     * If no named route exists the url is generated to a static page that the implementation may
     * choose.
     */
    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH) :string;
    
    /**
     * Generates a secure, absolute URL to the provided path.
     */
    public function secure(string $path, array $extra = []) :string;
    
    /**
     * Returns the canonical url for the current request.
     * i.e: current request: https://foo.com/foo?bar=baz
     * => https://foo.com/foo
     */
    public function canonical() :string;
    
    /**
     * The full current uri as a string including query, fragment etc.
     * Returns an absolute URL.
     */
    public function full() :string;
    
    /**
     * Get the previous URL based on the referer headers, including query string and fragment.
     * Returns an absolute URL.
     */
    public function previous(string $fallback = '/') :string;
    
}
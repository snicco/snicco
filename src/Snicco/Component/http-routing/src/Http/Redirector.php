<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;

interface Redirector
{

    /**
     * Tries to create a redirect response to a "home" route and falls back to "/" if no home route
     * exists.
     *
     * @param array<string,string|int> $arguments
     */
    public function home(array $arguments = [], int $status_code = 302): RedirectResponse;

    /**
     * @param array<string,string|int> $arguments
     * @throws RouteNotFound
     * @see UrlGeneratorInterface::toRoute()
     */
    public function toRoute(string $name, array $arguments = [], int $status_code = 302): RedirectResponse;

    /**
     * Redirect to the current path+query with a 302 status code.
     */
    public function refresh(): RedirectResponse;

    /**
     * Redirects the user the referer header or the fallback path if the header is not present.
     */
    public function back(string $fallback = '/', int $status_code = 302): RedirectResponse;

    /**
     * Redirects the user to the provided path and appends an "intended" query param with a value
     * of the current full url. Meant to be used in combination with {@see Redirector::intended()}
     *
     * @param array<string,string|int> $query
     *
     */
    public function deny(string $path, int $status_code = 302, array $query = []): RedirectResponse;

    /**
     * Looks for an "intended" query param and redirects the user to it.
     * Meant to be used in combination with
     * {@see Redirector::deny()}
     *
     * @param string $fallback If the query parameter is not present
     */
    public function intended(string $fallback = '/', int $status_code = 302): RedirectResponse;

    /**
     * Redirects to a path on the same domain.
     *
     * @param array<string,string|int> $query
     *
     * @see UrlGeneratorInterface::to()
     */
    public function to(string $path, int $status_code = 302, array $query = []): RedirectResponse;

    /**
     * Redirects to a path on the same domain. Will force a redirect to https if the current
     * request scheme is http.
     *
     * @param array<string,string|int> $query
     *
     */
    public function secure(string $path, int $status_code = 302, array $query = []): RedirectResponse;

    /**
     * This function SHOULD NEVER be used with user supplied input, or you are exposing yourself to
     * open-redirect exploits.
     */
    public function away(string $absolute_url, int $status_code = 302): RedirectResponse;

}
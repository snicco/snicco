<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Snicco\Component\HttpRouting\Routing\Exception\BadRouteParameter;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;

interface UrlGenerator
{
    /**
     * @var string
     */
    public const FRAGMENT_KEY = '_fragment';

    /**
     * Generate an absolute URL, e.g. 'https://example.com/foo/bar'.
     *
     * @var int
     */
    public const ABSOLUTE_URL = 0;

    /**
     * Generate an absolute path, e.g. '/foo/bar'.
     *
     * @var int
     */
    public const ABSOLUTE_PATH = 1;

    /**
     * @param string                   $path  the path MUST NOT be urlencoded
     * @param array<string,int|string> $extra The query arguments to append.
     *                                        A "_fragment" key can be passed to include a fragment after the query string.
     * @param bool|null                $https if null is passed the scheme of the current request will be used
     *
     * @return string an rfc-compliant url
     */
    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $https = null): string;

    /**
     * @param array<string,int|string> $arguments
     *
     * @throws RouteNotFound
     * @throws BadRouteParameter
     */
    public function toRoute(
        string $name,
        array $arguments = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $https = null
    ): string;

    /**
     * Tries to redirect to the routes in the following order: 'login'
     * 'auth.login' 'framework.auth.login' If no named route exists the url is
     * generated to a static page that the implementation may choose.
     *
     * @param array<string,int|string> $arguments
     */
    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string;
}

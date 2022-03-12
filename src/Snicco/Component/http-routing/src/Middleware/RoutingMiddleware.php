<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Exception\MethodNotAllowed;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

final class RoutingMiddleware extends Middleware
{
    private UrlMatcher $url_matcher;

    public function __construct(UrlMatcher $url_matcher)
    {
        $this->url_matcher = $url_matcher;
    }

    /**
     * @throws BadRouteConfiguration
     * @throws MethodNotAllowed
     */
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $result = $this->url_matcher->dispatch($request);

        if (! $result->isMatch()) {
            return $next($request);
        }

        return $next($request->withAttribute(RoutingResult::class, $result));
    }
}

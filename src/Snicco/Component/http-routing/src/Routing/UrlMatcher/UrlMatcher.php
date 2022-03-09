<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Exception\MethodNotAllowed;

interface UrlMatcher
{
    /**
     * @throws MethodNotAllowed
     * @throws BadRouteConfiguration
     */
    public function dispatch(Request $request): RoutingResult;
}

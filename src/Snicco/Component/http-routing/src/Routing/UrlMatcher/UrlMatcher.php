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
     * @todo bad routes are only found when dispatching. We might need a validate method on the
     *       UrlMatcher interface.
     */
    public function dispatch(Request $request): RoutingResult;

}
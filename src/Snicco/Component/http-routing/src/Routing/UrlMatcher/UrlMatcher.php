<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Exception\MethodNotAllowed;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;

/**
 * @api
 */
interface UrlMatcher
{
    
    /**
     * @throws MethodNotAllowed
     * @throws BadRouteConfiguration
     */
    public function dispatch(Request $request) :RoutingResult;
    
}
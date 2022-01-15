<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\UrlMatcher;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Routing\Exception\MethodNotAllowed;
use Snicco\HttpRouting\Routing\Exception\BadRouteConfiguration;

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
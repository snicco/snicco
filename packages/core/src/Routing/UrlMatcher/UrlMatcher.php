<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\UrlMatcher;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\Exception\MethodNotAllowed;
use Snicco\Core\Routing\Exception\BadRouteConfiguration;

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
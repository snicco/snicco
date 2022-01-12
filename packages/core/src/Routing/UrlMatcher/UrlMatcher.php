<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\UrlMatcher;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\Exception\BadRoute;
use Snicco\Core\Routing\Exception\MethodNotAllowed;

/**
 * @api
 */
interface UrlMatcher
{
    
    /**
     * @throws MethodNotAllowed
     * @throws BadRoute
     */
    public function dispatch(Request $request) :RoutingResult;
    
}
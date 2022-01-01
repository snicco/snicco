<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\Exceptions\BadRoute;
use Snicco\Core\Routing\Exceptions\MethodNotAllowed;

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
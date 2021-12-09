<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Routing\FastRoute\FastRouteUrlMatcher;

/**
 * @internal
 */
trait CreateRouteMatcher
{
    
    public function createRouteMatcher() :FastRouteUrlMatcher
    {
        return new FastRouteUrlMatcher();
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\Condition;

use Snicco\HttpRouting\Http\Psr7\Request;

/**
 * @interal
 */
final class IsAdminDashboardRequest extends AbstractRouteCondition
{
    
    public function isSatisfied(Request $request) :bool
    {
        return $request->isToAdminArea();
    }
    
}
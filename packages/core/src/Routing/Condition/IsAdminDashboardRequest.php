<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Condition;

use Snicco\Core\Http\Psr7\Request;

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
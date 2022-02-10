<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

/**
 * @interal
 */
final class IsAdminDashboardRequest extends RouteCondition
{

    public function isSatisfied(Request $request): bool
    {
        return $request->isToAdminArea();
    }

}
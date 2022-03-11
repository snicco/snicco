<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Conditions;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;

final class TrueRouteCondition extends RouteCondition
{
    public function isSatisfied(Request $request): bool
    {
        return true;
    }

    public function getArguments(Request $request): array
    {
        return [];
    }
}

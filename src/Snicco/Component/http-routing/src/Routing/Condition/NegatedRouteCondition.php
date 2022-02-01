<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

/**
 * @interal
 */
final class NegatedRouteCondition extends AbstractRouteCondition
{

    private AbstractRouteCondition $condition;

    public function __construct(AbstractRouteCondition $condition)
    {
        $this->condition = $condition;
    }

    public function isSatisfied(Request $request): bool
    {
        return !$this->condition->isSatisfied($request);
    }

    public function getArguments(Request $request): array
    {
        return $this->condition->getArguments($request);
    }

}

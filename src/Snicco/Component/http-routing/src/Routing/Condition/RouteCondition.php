<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

abstract class RouteCondition
{
    /**
     * @var string
     */
    public const NEGATE = '!';

    abstract public function isSatisfied(Request $request): bool;

    /**
     * Get an array of arguments that will be merged with the url segments and
     * passed to the controller.
     *
     * @return array<string,string>
     */
    public function getArguments(Request $request): array
    {
        return [];
    }
}

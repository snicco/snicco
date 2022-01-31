<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

/**
 * @api
 */
abstract class AbstractRouteCondition
{

    const NEGATE = '!';

    abstract public function isSatisfied(Request $request): bool;

    /**
     * Get an array of arguments that will be merged with the url segments and passed to the
     * controller.
     *
     * @param Request $request
     *
     * @return array<mixed>
     */
    public function getArguments(Request $request): array
    {
        return [];
    }

}

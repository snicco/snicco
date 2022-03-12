<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Conditions;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;

final class MaybeRouteCondition extends RouteCondition
{
    /**
     * @var bool|string
     */
    private $make_it_pass;

    public function __construct($make_it_pass)
    {
        $this->make_it_pass = $make_it_pass;
    }

    public function isSatisfied(Request $request): bool
    {
        $val = $GLOBALS['test']['maybe_condition_run'] ?? 0;
        ++$val;
        $GLOBALS['test']['maybe_condition_run'] = $val;

        return true === $this->make_it_pass || 'foobar' === $this->make_it_pass;
    }

    public function getArguments(Request $request): array
    {
        return [];
    }
}

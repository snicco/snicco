<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Conditions;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;

final class RouteConditionWithArgs extends RouteCondition
{
    private bool $make_it_pass;

    private string $val;

    public function __construct(string $val, bool $make_it_pass)
    {
        $this->val = $val;
        $this->make_it_pass = $make_it_pass;
    }

    public function isSatisfied(Request $request): bool
    {
        return $this->make_it_pass;
    }

    /**
     * @return array{condition_arg: string}
     */
    public function getArguments(Request $request): array
    {
        return [
            'condition_arg' => $this->val,
        ];
    }
}

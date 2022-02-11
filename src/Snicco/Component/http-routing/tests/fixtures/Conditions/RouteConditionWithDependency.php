<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Conditions;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;

class RouteConditionWithDependency extends RouteCondition
{

    private bool $make_it_pass;
    private Foo $foo;

    public function __construct(Foo $foo, bool $make_it_pass)
    {
        $this->foo = $foo;
        $this->make_it_pass = $make_it_pass;
    }

    public function isSatisfied(Request $request): bool
    {
        return $this->make_it_pass;
    }

    public function getArguments(Request $request): array
    {
        return ['foo' => $this->foo->value];
    }

}
<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

interface RouteConditionFactory
{
    public function __invoke(ConditionBlueprint $blueprint): RouteCondition;
}

<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use function array_values;

final class NewableRouteConditionFactory implements RouteConditionFactory
{
    public function __invoke(ConditionBlueprint $blueprint): RouteCondition
    {
        $class = $blueprint->class;
        $args = $blueprint->passed_args;

        /** @psalm-suppress UnsafeInstantiation */
        $instance = new $class(...array_values($args));

        if ($blueprint->is_negated) {
            return new NegatedRouteCondition($instance);
        }

        return $instance;
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use InvalidArgumentException;

use function array_shift;
use function is_subclass_of;
use function sprintf;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class ConditionBlueprint
{

    public bool $is_negated = false;

    /**
     * @var class-string<RouteCondition>
     */
    public string $class;

    public array $passed_args;

    /**
     * @param class-string<RouteCondition>|"!" $condition_class
     */
    public function __construct(string $condition_class, array $arguments = [])
    {
        if ($condition_class === RouteCondition::NEGATE) {
            /** @var string $condition_class */
            $condition_class = array_shift($arguments);
            $this->is_negated = true;
        }

        if (!is_subclass_of($condition_class, RouteCondition::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    "A condition has to be an instance of [%s].\nGot [%s].",
                    RouteCondition::class,
                    $condition_class
                )
            );
        }

        $this->class = $condition_class;
        $this->passed_args = $arguments;
    }

}

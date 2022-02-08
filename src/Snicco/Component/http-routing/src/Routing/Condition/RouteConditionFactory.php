<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use Closure;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

/**
 * @interal
 */
final class RouteConditionFactory
{

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @psalm-suppress MixedAssignment
     */
    public function create(ConditionBlueprint $blueprint): AbstractRouteCondition
    {
        $class = $blueprint->class;
        $args = $blueprint->passed_args;

        try {
            $instance = $this->container->get($class);
            if ($instance instanceof Closure) {
                $instance = $instance(...array_values($args));
            }
            if (!$instance instanceof AbstractRouteCondition) {
                throw new LogicException(
                    sprintf(
                        "Resolving a route condition from the container must return an instance of [%s].\nGot [%s]",
                        AbstractRouteCondition::class,
                        is_object($instance) ? get_class($instance) : gettype($instance)
                    )
                );
            }
        } catch (NotFoundExceptionInterface $e) {
            // Don't check if the entry is in the container with has since many DI-containers
            // are capable of constructing the service with auto-wiring.
            $instance = (new ReflectionClass($class))->newInstanceArgs(array_values($args));
        }

        if ($blueprint->is_negated) {
            return new NegatedRouteCondition($instance);
        }

        return $instance;
    }

}

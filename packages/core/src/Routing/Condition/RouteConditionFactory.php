<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Condition;

use Closure;
use LogicException;
use Webmozart\Assert\Assert;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

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
     */
    public function create(ConditionBlueprint $blueprint) :AbstractRouteCondition
    {
        $class = $blueprint->class();
        $args = $blueprint->passedArgs();
        
        if ($this->container->has($class)) {
            $instance = $this->container->get($class);
            if ($instance instanceof Closure) {
                $instance = $instance(...array_values($args));
            }
            if ( ! $instance instanceof AbstractRouteCondition) {
                throw new LogicException(
                    sprintf(
                        "Resolving a route condition from the container must return an instance of [%s].\nGot [%s]",
                        AbstractRouteCondition::class,
                        is_object($instance) ? get_class($instance) : gettype($instance)
                    )
                );
            }
        }
        else {
            $instance = new $class(...array_values($args));
        }
        
        Assert::isInstanceOf($instance, AbstractRouteCondition::class);
        
        if ($blueprint->isNegated()) {
            return new NegatedRouteCondition($instance);
        }
        
        return $instance;
    }
    
}

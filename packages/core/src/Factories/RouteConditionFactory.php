<?php

declare(strict_types=1);

namespace Snicco\Core\Factories;

use Webmozart\Assert\Assert;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Support\ReflectionDependencies;
use Snicco\Core\Routing\AbstractRouteCondition;
use Snicco\Core\Routing\Internal\ConditionBlueprint;
use Snicco\Core\Routing\Internal\NegateRouteCondition;

/**
 * @interal
 */
final class RouteConditionFactory
{
    
    private ContainerAdapter $container_adapter;
    
    public function __construct(ContainerAdapter $container_adapter)
    {
        $this->container_adapter = $container_adapter;
    }
    
    public function create(ConditionBlueprint $blueprint) :AbstractRouteCondition
    {
        $class = $blueprint->class();
        $args = $blueprint->passedArgs();
        
        $deps = new ReflectionDependencies($this->container_adapter);
        
        $deps = $deps->build($class, $args);
        
        /** @var AbstractRouteCondition $instance */
        $instance = new $class(...$deps);
        Assert::isInstanceOf($instance, AbstractRouteCondition::class);
        
        if ($blueprint->isNegated()) {
            return new NegateRouteCondition($instance);
        }
        
        return $instance;
    }
    
}

<?php

declare(strict_types=1);

namespace Tests\helpers;

use Snicco\Routing\RouteCollection;
use Snicco\Factories\ConditionFactory;
use SniccoAdapter\BaseContainerAdapter;
use Snicco\Factories\RouteActionFactory;

/**
 * @internal
 */
trait CreateRouteCollection
{
    
    protected function newCachedRouteCollection() :RouteCollection
    {
        
        $conditions = is_callable([$this, 'conditions']) ? $this->conditions() : [];
        $container = $this->container ?? new BaseContainerAdapter();
        
        $condition_factory = new ConditionFactory($conditions, $container);
        $handler_factory = new RouteActionFactory([], $container);
        
        return new RouteCollection(
            $this->createRouteMatcher(),
            $condition_factory,
            $handler_factory
        
        );
        
    }
    
}
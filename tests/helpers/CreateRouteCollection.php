<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Snicco\Factories\ConditionFactory;
    use Snicco\Factories\RouteActionFactory;
    use Snicco\Routing\RouteCollection;
    use SniccoAdapter\BaseContainerAdapter;

    /**
     * @internal
     */
    trait CreateRouteCollection
    {

        protected function newRouteCollection() : RouteCollection
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
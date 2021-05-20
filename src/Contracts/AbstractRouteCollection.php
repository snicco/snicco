<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteMatch;

    abstract class AbstractRouteCollection
    {

        /**
         * @var ConditionFactory
         */
        protected $condition_factory;

        /**
         * @var RouteActionFactory
         */
        protected $action_factory;


        abstract public function add(Route $route) : Route;

        abstract public function match(Request $request) : RouteMatch;

        abstract public function findByName(string $name) : ?Route;

        abstract public function withWildCardUrl(string $method) : array;

        abstract public function loadIntoDispatcher(string $method = null);

        protected function addToCollection(Route $route)
        {

            foreach ( $route->getMethods() as $method ) {

                $this->routes[$method][] = $route;

            }

        }

        protected function findInLookUps(string $name)
        {

            return $this->name_list[$name] ?? null;

        }

        protected function giveFactories(Route $route) : Route
        {

            $route->setActionFactory($this->action_factory);
            $route->setConditionFactory($this->condition_factory);
            return $route;
        }

        protected function findByRouteName(string $name) : ?Route
        {

            return collect($this->routes)
                ->flatten()
                ->first(function (Route $route) use ($name) {

                    return $route->getName() === $name;

                });

        }

        protected function findWildcardsInCollection(string $method) : array
        {

            return collect($this->routes[$method] ?? [])
                ->filter(function (Route $route) {

                    return trim($route->getUrl(), '/') === ROUTE::ROUTE_WILDCARD;

                })
                ->map(function (Route $route) {

                    return $this->giveFactories($route);
                })
                ->all();
        }

    }
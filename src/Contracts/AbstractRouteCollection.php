<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Support\Arr;
    use WPEmerge\Traits\ValidatesRoutes;

    abstract class AbstractRouteCollection
    {

        use ValidatesRoutes;

        /**
         * @var ConditionFactory
         */
        protected $condition_factory;

        /**
         * @var RouteActionFactory
         */
        protected $action_factory;

        /** @var RoutingResult|null */
        private $query_filter_routing_result;

        abstract public function add(Route $route) : Route;

        abstract public function findByName(string $name) : ?Route;

        abstract public function withWildCardUrl(string $method) : array;

        abstract public function loadIntoDispatcher(bool $global_routes ) :void;

        public function matchForQueryFiltering (Request $request) :RoutingResult {

            $result = $this->match($request);

            $this->query_filter_routing_result = $result;

            return $result;

        }

        public function match(Request $request) : RoutingResult
        {

            if (  $this->query_filter_routing_result ) {

                return $this->query_filter_routing_result;

            }

            $result = $this->route_matcher->find(
                $request->getMethod(),
                $request->routingPath()
            );

            if ( ! $route = $result->route() ) {

                return new RoutingResult(null);

            }

            $route = $this->giveFactories($route)->instantiateConditions();

            if ( ! $route->satisfiedBy($request) ) {

                return new RoutingResult(null);

            }

            $route->instantiateAction();

            return $result;

        }

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
                ->all();

        }

        protected function prepareOutgoingRoute( $routes ) :void
        {
             $routes = Arr::wrap($routes);

             collect($routes)->each(function (Route $route)  {

                $this->giveFactories($route);

            });


        }


    }
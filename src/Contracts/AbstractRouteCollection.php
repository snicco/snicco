<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Support\Arr;

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

        /** @var RoutingResult|null */
        private $route_result;

        abstract public function add(Route $route) : Route;

        abstract public function findByName(string $name) : ?Route;

        abstract public function withWildCardUrl(string $method) : array;

        abstract public function loadIntoDispatcher(string $method = null) :void;

        public function matchForQueryFiltering (Request $request) :RoutingResult {

            $result = $this->match($request);

            $this->route_result = $result;

            return $result;

        }

        public function match(Request $request) : RoutingResult
        {

            if (  $this->route_result ) {

                return $this->route_result;

            }

            $path = rawurldecode( $request->getRoutingPath() );

            $result = $this->route_matcher->find($request->getMethod(), $path);

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
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
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

        public function match(Request $request) : RoutingResult
        {

            $path = rawurldecode($request->path());

            $result = $this->route_matcher->find($request->getMethod(), $path);

            if ( ! $route = $result->route() ) {

                return $this->route_result = new RoutingResult(null);

            }

            $route = $this->giveFactories($route)->instantiateConditions();

            if ( ! $route->satisfiedBy($request) ) {

                return $this->route_result = new RoutingResult(null);

            }

            $route->instantiateAction();

            return $result;

        }

        public function hasResult() :?RoutingResult {

            return $this->route_result;

        }

        abstract public function add(Route $route) : Route;

        abstract public function findByName(string $name) : ?Route;

        abstract public function withWildCardUrl(string $method) : array;

        abstract public function loadIntoDispatcher(string $method = null) :void;

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
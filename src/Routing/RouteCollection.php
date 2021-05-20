<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Support\Arr;

    class RouteCollection extends AbstractRouteCollection
    {

        /**
         * An array of the routes keyed by method.
         *
         * @var array
         */
        protected $routes = [];

        /**
         * A look-up table of routes by their names.
         *
         * @var Route[]
         */
        protected $name_list = [];

        /**
         * @var RouteMatcher
         */
        protected $route_matcher;

        public function __construct(
            RouteMatcher $route_matcher,
            ConditionFactory $condition_factory,
            RouteActionFactory $action_factory
        ) {

            $this->route_matcher = $route_matcher;
            $this->condition_factory = $condition_factory;
            $this->action_factory = $action_factory;

        }

        public function add(Route $route) : Route
        {

            $this->addToCollection($route);

            $this->addLookups($route);

            return $route;

        }

        public function loadIntoDispatcher(string $method = null) : void
        {

            $all_routes = $this->routes;

            if ($method) {

                $all_routes = [$method => Arr::get($this->routes, $method, [])];

            }

            foreach ($all_routes as $method => $routes) {

                /** @var Route $route */
                foreach ($routes as $route) {

                    $this->route_matcher->add($route, [$method]);

                }

            }


        }

        public function findByName(string $name) : ?Route
        {

            $route = $this->findInLookUps($name);

            if ( ! $route) {

                $route = $this->findByRouteName($name);

            }
            if ( ! $route) {

                return null;

            }

            $this->prepareOutgoingRoute($route);

            return $route;

        }

        public function withWildCardUrl(string $method) : array
        {
            $this->prepareOutgoingRoute( $routes = $this->findWildcardsInCollection($method) );

            return $routes;

        }

        private function addLookups(Route $route)
        {

            if ($name = $route->getName()) {

                $this->name_list[$name] = $route;

            }

        }


    }
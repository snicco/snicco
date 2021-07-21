<?php


    declare(strict_types = 1);


    namespace Snicco\Routing;

    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Contracts\RouteMatcher;
    use Snicco\ExceptionHandling\Exceptions\ConfigurationException;
    use Snicco\Support\WP;
    use Snicco\Factories\ConditionFactory;
    use Snicco\Factories\RouteActionFactory;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Arr;

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

        protected $already_added = [];

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

        /**
         * @throws ConfigurationException
         */
        public function loadIntoDispatcher(bool $global_routes ) : void
        {

            $all_routes = $this->routes;

            foreach ($all_routes as $method => $routes) {

                /** @var Route $route */
                foreach ($routes as $route) {

                    if ( $this->wasAlreadyAdded($route, $method) ) {
                        continue;
                    }

                    $this->validateAttributes($route);

                    $this->route_matcher->add($route, [$method]);

                    $this->already_added[$method][] = $route->getUrl();

                }

            }


        }

        /**
         *
         * Dont load a Route twice. This can happen if a users includes a file inside
         * globals.php or if he attempts to override an inbuilt route.
         * In this case the first route takes priority which is almost always the user-defined route.
         *
         * @param  Route  $route
         * @param  string  $method
         *
         * @return bool
         */
        private function wasAlreadyAdded(Route $route, string $method) : bool
        {

            if ( ! isset($this->already_added[$method] ) ) {
                return false;
            }

            return in_array($route->getUrl(), $this->already_added[$method] );

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

            $name = $route->getName();

            if ($name && ! isset($this->name_list[$name])) {

                $this->name_list[$name] = $route;

            }

        }


    }
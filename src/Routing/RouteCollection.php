<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use FastRoute\Dispatcher;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Support\Arr;

    class RouteCollection
    {

        /**
         * An array of the routes keyed by method.
         *
         * @var array
         */
        private $routes = [];

        /**
         * A look-up table of routes by their names.
         *
         * @var Route[]
         */
        private $name_list = [];

        /**
         * @var RouteMatcher
         */
        private $route_matcher;

        /**
         * @var bool
         */
        private $loaded_routes = false;

        private $matched_route;

        /**
         * @var ConditionFactory
         */
        private $condition_factory;

        /**
         * @var RouteActionFactory
         */
        private $action_factory;

        public function __construct(
            RouteMatcher $route_matcher,
            ConditionFactory $condition_factory,
            RouteActionFactory $action_factory
        ) {

            $this->route_matcher = $route_matcher;
            $this->condition_factory = $condition_factory;
            $this->action_factory = $action_factory;

        }

        public function match(Request $request) : RouteMatch
        {

            $match = $this->matchPathAgainstLoadedRoutes($request);

            $this->matched_route = $match;

            if ( ! $match->route() ) {

                return $match;

            }

            $route = $match->route();
            $route_url_args = $match->capturedUrlSegmentValues();

            $route_url_args = array_map(function ($value) {
                return rtrim($value, '/');
            }, $route_url_args );

            return new RouteMatch(
                $route,
                $route_url_args
            );


        }

        public function add(Route $route) : Route
        {

            $this->addToCollection($route);

            $this->addLookups($route);

            return $route;

        }

        public function findByName(string $name) : ?Route
        {

            $route = $this->findInLookUps($name);

            if ( ! $route) {

                $route = $this->findByRouteName($name);

            }

            return ($route) ? $this->giveFactories($route) : null;


        }

        public function withWildCardUrl(string $method) : array
        {

            return collect($this->routes[$method] ?? [])
                ->filter(function (Route $route) {

                    return trim($route->getUrl(), '/') === ROUTE::ROUTE_WILDCARD;

                })
                ->map(function (Route $route ) {
                    return $this->giveFactories($route);
                })
                ->all();

        }

        private function giveFactories (Route $route) :Route {

            $route->setActionFactory($this->action_factory);
            $route->setConditionFactory($this->condition_factory);
            return $route;

        }

        private function findByRouteName(string $name) : ?Route
        {

            return collect($this->routes)
                ->flatten()
                ->first(function (Route $route) use ($name) {

                    return $route->getName() === $name;

                });

        }

        private function findInLookUps(string $name) : ?Route
        {

            return $this->name_list[$name] ?? null;

        }

        private function addToCollection(Route $route)
        {

            foreach ( $route->getMethods() as $method ) {

                $this->routes[$method][] = $route;

            }

        }

        private function addLookups(Route $route)
        {

            if ($name = $route->getName()) {

                $this->name_list[$name] = $route;

            }

        }

        public function loadIntoDispatcher(string $method = null)
        {

            if ($this->route_matcher->isCached() || $this->loaded_routes) {

                return;

            }

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

        /** @todo the changing of the url should be a global middleware that adds an attribute to the Request object */
        private function matchPathAgainstLoadedRoutes(Request $request) : RouteMatch
        {

            $path = $request->path();

            if (WP::isAdmin() && ! WP::isAdminAjax()) {

                $path = $path.'/'.$request->query('page', '');

            }

            if (WP::isAdminAjax()) {

                $path = $path.'/'.$request->parsedBody('action', $request->query('action', ''));

            }

            return $this->dispatchToRouteMatcher($request, $path);


        }

        private function dispatchToRouteMatcher(Request $request, $url) : RouteMatch
        {

            $route_match = $this->route_matcher->find($request->getMethod(), $url);

            if ( ! $route = $route_match->route()) {

                return $route_match;

            }

            $this->giveFactories($route);

            $route->instantiateConditions();

            if ( ! $route->satisfiedBy($request) ) {

                return new RouteMatch(null, []);

            }

            $route->instantiateAction();

            return new RouteMatch($route, $route_match->capturedUrlSegmentValues());

        }

        public function currentMatch() : ?RouteMatch
        {

            return $this->matched_route;

        }


    }
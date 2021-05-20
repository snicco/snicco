<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteResult;

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

        /** @var RouteResult|null */
        private $route_result;

        public function match(Request $request) : RouteResult
        {

            $path = $this->appendSpecialWpSuffixesToPath($request);

            $result = $this->route_matcher->find($request->getMethod(), $path);

            if ( ! $route = $result->route() ) {

                return $this->route_result = new RouteResult(null);

            }

            $route = $this->giveFactories($route)->instantiateConditions();

            if ( ! $route->satisfiedBy($request) ) {

                return $this->route_result = new RouteResult(null);

            }

            $route->instantiateAction();

            return $result;

        }

        public function hasResult() :?RouteResult {

            return $this->route_result;

        }

        abstract public function add(Route $route) : Route;

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

        /** @todo Make this function a middleware that adds to a request attribute */
        private function appendSpecialWpSuffixesToPath(Request $request) : string
        {

            $path = $request->path();

            if (WP::isAdmin() && ! WP::isAdminAjax()) {

                $path = $path.'/'.$request->query('page', '');

            }

            if (WP::isAdminAjax()) {

                $path = $path.'/'.$request->parsedBody('action', $request->query('action', ''));

            }

            return $path;
        }


    }
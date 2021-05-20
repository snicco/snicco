<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;

    class CachedRouteCollection extends AbstractRouteCollection
    {

        /**
         * @var CachedFastRouteMatcher
         */
        private $route_matcher;

        /**
         * @var ConditionFactory
         */
        private $condition_factory;

        /**
         * @var RouteActionFactory
         */
        private $action_factory;

        private $routes = [];

        private $name_list = [];

        /**
         * @var string
         */
        private $cache_file;

        private $route_objects = [];

        public function __construct(
            CachedFastRouteMatcher $route_matcher,
            ConditionFactory $condition_factory,
            RouteActionFactory $action_factory,
            string $cache_file
        ) {

            $this->route_matcher = $route_matcher;
            $this->condition_factory = $condition_factory;
            $this->action_factory = $action_factory;
            $this->cache_file = $cache_file;

            if ( file_exists($cache_file ) ) {

                $cache = require $cache_file;
                $this->name_list = $cache['lookups'];
                $this->routes = $cache['routes'];

            }


        }

        public function add(Route $route) : Route
        {

            $this->addAsRouteObject($route);

            return $route;

        }

        public function match(Request $request) : RouteMatch
        {

            $path = $request->path();

            if (WP::isAdmin() && ! WP::isAdminAjax()) {

                $path = $path.'/'.$request->query('page', '');

            }

            if (WP::isAdminAjax()) {

                $path = $path.'/'.$request->parsedBody('action', $request->query('action', ''));

            }

            $route_match = $this->route_matcher->find($request->getMethod(), $path);

            if ( ! $route = $route_match->route()) {

                return $route_match;

            }

            $this->giveFactories($route);

            $route->instantiateConditions();

            if ( ! $route->satisfiedBy($request)) {

                return new RouteMatch(null, []);

            }

            $route_url_args = $route_match->capturedUrlSegmentValues();

            $route_url_args = array_map(function ($value) {
                return rtrim($value, '/');
            }, $route_url_args );

            $route->instantiateAction();

            return new RouteMatch(
                $route,
                $route_url_args
            );

        }

        public function findByName(string $name) : ?Route
        {

            $route = $this->name_list[$name] ?? null;

            if ($route) {

                $route = Route::hydrate($route);

            }

            if ( ! $route ) {

                $route = $this->findInCollection( $name);

            }

            if ( ! $route ) {

                return null;

            }

            $this->giveFactories($route);

            return $route;


        }

        private function findInCollection(string $name)
        {
            return collect($this->route_objects)
                ->flatten()
                ->first(function (Route $route) use ($name) {

                    return $route->getName() === $name;

                });
        }

        public function withWildCardUrl(string $method) : array
        {

            return [];
        }

        public function loadIntoDispatcher(string $method = null)
        {

            if (file_exists($this->cache_file)) {

                return;

            }

            $this->loadOneTime();

            $this->createCacheFile();


        }

        private function addAsRouteObject(Route $route)
        {

            foreach ($methods = $route->getMethods() as $method) {

                $this->route_objects[$method][] = $route;

            }


        }

        private function loadOneTime()
        {

            foreach ($this->route_objects as $method => $routes) {

                /** @var Route $route */
                foreach ($routes as $route) {

                    $this->route_matcher->add($route, [$method]);

                }

            }
        }

        private function createCacheFile()
        {

            $lookups = collect($this->route_objects)
                ->flatten()
                ->filter(function( Route $route ) {

                return $route->getName() !== null && $route->getName() !== '';

            })
                ->flatMap(function(Route $route) {

                return [$route->getName() => $route->asArray()];

            })
                ->all();

            $array_routes = [];

            foreach ($this->route_objects as $method => $routes) {

                foreach ($routes as $route) {

                    /** @var Route $route  */
                    $array_routes[$method][] = $route->asArray();
                }

            }

            $combined = ['routes' => $array_routes, 'lookups' => $lookups];

            file_put_contents(
                $this->cache_file,
                '<?php
declare(strict_types=1); return '.var_export($combined, true).';'
            );

        }

        private function giveFactories(Route $route)
        {

            $route->setActionFactory($this->action_factory);
            $route->setConditionFactory($this->condition_factory);

        }


    }
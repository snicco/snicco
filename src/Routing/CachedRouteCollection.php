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
        protected $route_matcher;

        protected $routes = [];

        protected $name_list = [];

        /**
         * @var string
         */
        private $cache_file;

        private $cached_routes = [];

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
                $this->cached_routes = $cache['routes'];

            }


        }

        public function add(Route $route) : Route
        {

            $this->addToCollection($route);

            return $route;

        }

        public function loadIntoDispatcher(string $method = null)
        {

            if (file_exists($this->cache_file)) {

                return;

            }

            $this->loadOneTime();

            $this->createCacheFile();


        }

        public function findByName(string $name) : ?Route
        {

            $route = $this->findInLookUps($name);

            if ($route) {

                $route = Route::hydrate($route);

            }

            if ( ! $route ) {

                $route = $this->findByRouteName( $name);

            }

            if ( ! $route ) {

                return null;

            }

            return $this->giveFactories($route);


        }

        public function withWildCardUrl(string $method) : array
        {
            $routes = $this->findCachedWildcardRoutes($method);

            if( count($routes) ) {

                return $routes;

            }

           return $this->findWildcardsInCollection($method);

        }

        private function findCachedWildcardRoutes(string $method) : array
        {

            $routes = collect($this->cached_routes[$method] ?? [])
                ->filter(function (array $route) {

                    return trim($route['url'], '/') === ROUTE::ROUTE_WILDCARD;

                })
                ->map(function (array $route ) {
                    return $this->giveFactories(Route::hydrate($route));
                });

            return $routes->all();

        }

        private function loadOneTime()
        {

            foreach ($this->routes as $method => $routes) {

                /** @var Route $route */
                foreach ($routes as $route) {

                    $this->route_matcher->add($route, [$method]);

                }

            }
        }

        private function createCacheFile()
        {

            $lookups = collect($this->routes)
                ->flatten()
                ->filter(function( Route $route ) {

                return $route->getName() !== null && $route->getName() !== '';

            })
                ->flatMap(function(Route $route) {

                return [$route->getName() => $route->asArray()];

            })
                ->all();

            $array_routes = [];

            foreach ($this->routes as $method => $routes) {

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


    }
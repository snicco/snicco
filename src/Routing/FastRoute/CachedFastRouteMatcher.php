<?php


    declare(strict_types = 1);


    namespace Snicco\Routing\FastRoute;

    use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
    use Snicco\Contracts\RouteMatcher;
    use Snicco\Routing\Route;
    use Snicco\Routing\RoutingResult;
    use Snicco\Traits\PreparesRouteForExport;

    class CachedFastRouteMatcher implements RouteMatcher
    {

        use HydratesFastRoutes;
        use TransformFastRoutes;
        use PreparesRouteForExport;

        private FastRouteMatcher $uncached_matcher;
        private array            $route_cache;
        private string           $route_cache_file;

        public function __construct(FastRouteMatcher $uncached_matcher, string $route_cache_file)
        {

            $this->uncached_matcher = $uncached_matcher;
            $this->uncached_matcher->setRouteStoragePreparation(function (Route $route) {

                return $this->serializeRoute($route);

            });
            $this->route_cache_file = $route_cache_file;

            if (file_exists($route_cache_file)) {

                $this->route_cache = require $route_cache_file;

            }

        }

        public function add(Route $route, $methods)
        {

            $this->uncached_matcher->add($route, $methods);

        }

        public function find(string $method, string $path) : RoutingResult
        {

            if ( $this->route_cache ) {

                $dispatcher = new RouteDispatcher($this->route_cache);

                return $this->hydrateRoutingResult(
                   $this->toRoutingResult( $dispatcher->dispatch($method, $path) )
                );

            }

            $this->createCache(
                $this->uncached_matcher->getRouteMap()
            );

            $routing_result = $this->uncached_matcher->find($method, $path);

            return $this->hydrateRoutingResult($routing_result);

        }

        private function createCache(array $route_data)
        {

            file_put_contents(
                $this->route_cache_file,
                '<?php
declare(strict_types=1); return '.var_export($route_data, true).';'
            );

        }

        public function isCached() : bool
        {
            return is_array($this->route_cache);
        }

        private function serializeRoute(Route $route) : array
        {
            return $this->prepareForVarExport($route->asArray());

        }


    }
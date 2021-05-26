<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;

	use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Support\Str;
    use WPEmerge\Traits\PreparesRouteForExport;

    class CachedFastRouteMatcher implements RouteMatcher {

        use HydratesFastRoutes;
        use PreparesRouteForExport;

		/**
		 * @var FastRouteMatcher
		 */
		private $uncached_matcher;

		/**
		 * @var array
		 */
		private $route_cache;

		/**
		 * @var string
		 */
		private $route_cache_file;

		public function __construct( FastRouteMatcher $uncached_matcher, string $route_cache_file ) {

			$this->uncached_matcher = $uncached_matcher;

			$this->route_cache_file = $route_cache_file;

			if ( file_exists( $route_cache_file ) ) {

				$this->route_cache = require $route_cache_file;

			}

		}

		public function add( Route $route , $methods) {

		   $this->serializeRouteAttributes($route);

			$this->uncached_matcher->add( $route , $methods );

		}

		public function find( string $method, string $path ) :RoutingResult {

			if ( $this->route_cache ) {

				$dispatcher = new RouteDispatcher( $this->route_cache );

				return $this->hydrate($dispatcher->dispatch( $method, $path ));

			}

			$this->createCache(

			    $this->uncached_matcher->getRouteMap()

            );

			return $this->uncached_matcher->find( $method, $path );

		}

		private function createCache( array $route_data ) {

			file_put_contents(
				$this->route_cache_file,
				'<?php
declare(strict_types=1); return '. var_export( $route_data, true ) . ';'
			);

		}

		public function isCached() : bool {

			return is_array($this->route_cache);

		}

        private function serializeRouteAttributes(Route $route) {

            $route->handle($this->serializeAttribute($route->getAction()));

            if ( ( $query_filter = $route->getQueryFilter() ) instanceof \Closure) {

                $route->setQueryFilter($this->serializeAttribute($query_filter));

            }

        }


	}
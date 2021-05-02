<?php


	namespace WPEmerge\Routing;

	use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
	use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use FastRoute\RouteCollector;
	use FastRoute\RouteParser\Std as RouteParser;
	use WPEmerge\Contracts\CreatesCacheableRoutes;
	use WPEmerge\Contracts\RouteMatcher;

	class FastRouteMatcher implements RouteMatcher, CreatesCacheableRoutes {

		/**
		 * @var \FastRoute\RouteCollector
		 */
		private $collector;

		public function __construct() {

			$this->collector = new RouteCollector( new RouteParser(), new DataGenerator() );

		}

		public function add( $methods, string $uri, $handler ) {

			$this->collector->addRoute( $methods, $uri, $handler );

		}

		public function find( string $method, string $path ) {

			$dispatcher = new RouteDispatcher( $this->collector->getData() );

			return $dispatcher->dispatch( $method, $path );


		}

		public function getRouteMap() : array {

			return $this->collector->getData() ?? [];

		}

		public function isCached() : bool {

			return false;

		}

	}


	class CachedFastRouteMatcher implements RouteMatcher {

		/**
		 * @var \WPEmerge\Routing\FastRouteMatcher
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

		public function add( $methods, string $uri, $handler ) {

			$this->uncached_matcher->add( $methods, $uri, $handler );

		}

		public function find( string $method, string $path ) {

			if ( $this->route_cache ) {

				$dispatcher = new RouteDispatcher( $this->route_cache );

				return $dispatcher->dispatch( $method, $path );

			}

			$this->createCache( $this->uncached_matcher->getRouteMap() );

			return $this->uncached_matcher->find( $method, $path );

		}

		private function createCache( array $route_data ) {

			file_put_contents(
				$this->route_cache_file,
				'<?php return ' . var_export( $route_data, true ) . ';'
			);

		}

		public function isCached() : bool {

			return is_array($this->route_cache);

		}

	}



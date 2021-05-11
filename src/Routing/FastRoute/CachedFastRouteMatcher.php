<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;

	use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use WPEmerge\Contracts\RouteMatcher;



	class CachedFastRouteMatcher implements RouteMatcher {

		/**
		 * @var \WPEmerge\Routing\FastRoute\FastRouteMatcher
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

		public function find( string $method, string $path ) :array {

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
				'<?php
declare(strict_types=1); return ' . var_export( $route_data, true ) . ';'
			);

		}

		public function isCached() : bool {

			return is_array($this->route_cache);

		}

		public function canBeCached() {

			return true;

		}

	}
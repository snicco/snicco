<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;


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

		public function find( string $method, string $path ) : array {

			$dispatcher = new RouteDispatcher( $this->collector->getData() );

			return $dispatcher->dispatch( $method, $path );


		}

		public function getRouteMap() : array {

			return $this->collector->getData() ?? [];

		}

		public function isCached() : bool {

			return false;

		}

		public function canBeCached() : bool {

			return false;

		}

	}
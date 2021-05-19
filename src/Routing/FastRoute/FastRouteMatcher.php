<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;

	use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
	use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use FastRoute\RouteCollector;
	use FastRoute\RouteParser\Std as RouteParser;
	use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Routing\Route;
    use WPEmerge\Support\Str;

    class FastRouteMatcher implements RouteMatcher {

		/**
		 * @var RouteCollector
		 */
		private $collector;

        /**
         * @var FastRouteSyntax
         */
        private $route_regex;

        public function __construct() {

			$this->collector = new RouteCollector( new RouteParser(), new DataGenerator() );
            $this->route_regex = new FastRouteSyntax();

		}

		public function add( CompiledRoute $route, array $methods ) {

            $url = $this->route_regex->convert($route);

			$this->collector->addRoute( $methods, $url, (array) $route );

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




    }
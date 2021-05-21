<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;

	use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
    use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use FastRoute\RouteCollector;
	use FastRoute\RouteParser\Std as RouteParser;
	use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;

    class FastRouteMatcher implements RouteMatcher {

        use HydratesFastRoutes;

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

		public function add( Route $route , array $methods ) {

            $url = $this->convertUrl($route);

			$this->collector->addRoute( $methods, $url, $route->asArray() );

		}

		private function convertUrl(Route $route) : string
        {

            return $this->route_regex->convert($route);

        }

		public function find( string $method, string $path ) : RoutingResult {

			$dispatcher = new RouteDispatcher( $this->collector->getData() );

			$route_info = $dispatcher->dispatch( $method, $path );

            return $this->hydrate($route_info);

		}

		public function getRouteMap() : array {

			return $this->collector->getData() ?? [];

		}

		public function isCached() : bool {

			return false;

		}




    }
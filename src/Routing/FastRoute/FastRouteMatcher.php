<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;


	use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
	use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use FastRoute\RouteCollector;
	use FastRoute\RouteParser\Std as RouteParser;
	use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Support\Url;

    class FastRouteMatcher implements RouteMatcher {

		/**
		 * @var RouteCollector
		 */
		private $collector;

		public function __construct() {

			$this->collector = new RouteCollector( new RouteParser(), new DataGenerator() );

		}

		public function add( CompiledRoute $route, string $current_method ) {

            $url = $route->url;

			$this->collector->addRoute( $current_method, $this->normalizePath($url), (array) $route );

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

        /**
         * @todo FastRoute does indeed support trailing slashes. Right now is impossible to create trailing slash routes.
         * @link https://github.com/nikic/FastRoute/issues/106
         */
        private function normalizePath(string $path) : string
        {

            return Url::toRouteMatcherFormat($path);

        }


    }
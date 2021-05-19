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
         * @var FastRouteRegex
         */
        private $route_regex;

        public function __construct() {

			$this->collector = new RouteCollector( new RouteParser(), new DataGenerator() );
            $this->route_regex = new FastRouteRegex();

		}

		public function add( CompiledRoute $route, array $methods ) {

            $url = $this->convertUrlToFastRouteSyntax($route);

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

        private function convertUrlToFastRouteSyntax (CompiledRoute $route) : string
        {
                                                    
            $url = $route->url;

            if ( trim( $url, '/' ) === Route::ROUTE_WILDCARD ) {

                $url = '__generated:wp_route_no_url_condition_' . Str::random(16);

            }

            $url = $this->route_regex->convertOptionalSegments($url);

            foreach ($route->regex as $regex) {

                $url = $this->route_regex->addCustomRegexToSegments($regex, $url);

            }

            if ( $route->trailing_slash ) {

               $url = $this->ensureRouteOnlyMatchesWithTrailingSlash($url, $route);

            }

            return $url;

        }

        private function ensureRouteOnlyMatchesWithTrailingSlash ($url, CompiledRoute $route) : string
        {

            foreach ($route->segment_names as $segment) {

                $url = $this->route_regex->addCustomRegexToSegments( [$segment => '[^\/]+\/?'], $url );

            }

            return Str::replaceFirst('[/', '/[', $url);

        }


    }
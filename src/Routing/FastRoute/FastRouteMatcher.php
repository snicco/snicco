<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\FastRoute;

	use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
    use FastRoute\Dispatcher;
    use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
	use FastRoute\RouteCollector;
	use FastRoute\RouteParser\Std as RouteParser;
	use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCompiler;
    use WPEmerge\Routing\RouteMatch;
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

        /**
         * @var RouteCompiler
         */
        private $compiler;

        public function __construct(RouteCompiler $compiler) {

			$this->collector = new RouteCollector( new RouteParser(), new DataGenerator() );
            $this->route_regex = new FastRouteSyntax();
            $this->compiler = $compiler;

        }

		public function add( Route $route, array $methods ) {

            $url = $this->route_regex->convert($route);

			$this->collector->addRoute( $methods, $url, $route->toArray() );

		}

		public function find( string $method, string $path ) : RouteMatch {

			$dispatcher = new RouteDispatcher( $this->collector->getData() );

			$route_info = $dispatcher->dispatch( $method, $path );

            if ($route_info[0] !== Dispatcher::FOUND)  {

                return new RouteMatch(null, []);

            }

            $route = $route_info[1];
            $payload = $route_info[2];

            return new RouteMatch($route, $payload);

		}

		public function getRouteMap() : array {

			return $this->collector->getData() ?? [];

		}

		public function isCached() : bool {

			return false;

		}




    }
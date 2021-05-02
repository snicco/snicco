<?php


	namespace WPEmerge\Routing;

	use FastRoute\Dispatcher;
	use FastRoute\RouteCollector;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteMatcher;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Support\Arr;

	use WPEmerge\Support\Str;

	use function FastRoute\simpleDispatcher;

	class RouteCollection {

		const hash_key = 'static_url';

		/** @var ConditionFactory */
		private $condition_factory;

		/** @var HandlerFactory */
		private $handler_factory;

		/**
		 * An array of the routes keyed by method.
		 *
		 * @var array
		 */
		private $routes = [];

		/**
		 * A look-up table of routes by their names.
		 *
		 * @var Route[]
		 */
		private $name_list = [];

		/**
		 * @var \WPEmerge\Contracts\RouteMatcher
		 */
		private $route_matcher;

		public function __construct(
			ConditionFactory $condition_factory,
			HandlerFactory $handler_factory,
			RouteMatcher $route_matcher
		) {

			$this->condition_factory = $condition_factory;
			$this->handler_factory   = $handler_factory;
			$this->route_matcher     = $route_matcher;

		}

		public function add( Route $route ) : Route {

			$this->addToCollection( $route );

			$this->addLookups( $route );

			return $route;

		}

		public function findByName( string $name ) : ?Route {

			$route = $this->name_list[ $name ] ?? null;

			if ( $route ) {

				return $route->compileConditions( $this->condition_factory );

			}

			$route = collect( $this->routes )->flatten()->first( function ( Route $route ) use ( $name ) {

				return $route->getName() === $name;

			} );

			return ( $route ) ? $route->compileConditions( $this->condition_factory ) : null;

		}

		public function match( RequestInterface $request ) : RouteMatch {


			$this->loadRoutes( $request->getMethod() );

			$match = $this->findRoute($request);

			if ( ! $match->route() ) {

				return $match;

			}

			$route = $match->route();
			$original_payload = $match->payload();
			$condition_args = [];

			foreach ( $route->getCompiledConditions() as $compiled_condition ) {

				$args = $compiled_condition->getArguments( $request );

				$condition_args = array_merge( $condition_args, $args );

			}

			$route->compileAction( $this->handler_factory );
			$request->setRoute( $route );

			return new RouteMatch(
				$route,
				array_merge( $condition_args, $original_payload )
			);


		}

		private function addToCollection( Route $route ) {

			foreach( $route->getMethods() as $method ) {

				$this->routes[ $method ][] = $route;

			}

		}

		private function addLookups( Route $route ) {

			if ( $name = $route->getName() ) {

				$this->name_list[ $name ] = $route;

			}

		}

		// For static routes we need to place a randomized prefix before the request path.
		// This prefix gets stripped later when we match routes.
		// This is necessary to allow researching for a possible dynamic route after a static route
		// that was found in the dispatcher failed due to custom conditions.
		// $request->path = 'foo/bar' would result in first looking for a static route:
		// 3451342sf31a/foo/'bar' and if that fails for a dynamic route that matches /foo/bar
		private function loadRoutes( string $method) {

			if ( $this->route_matcher->isCached() ) {

				return;

			}

			$routes = Arr::get( $this->routes, $method, [] );

			/** @var Route $route */
			foreach ( $routes as $route ) {

				$url = UrlParser::isDynamicUrl($url = $route->url())
					? $route->getCompiledUrl()
					: $this->hash($url);

				$this->route_matcher->add( $method, $url , $route );

			}


		}

		private function satisfiesCustomConditions( Route $route, RequestInterface $request ) : bool {

			$route->compileConditions( $this->condition_factory );

			return $route->matches( $request );

		}

		private function hash( string $path ) : string {

			$path = trim( $path, '/' );

			return md5( static::hash_key ) . '/' . $path;

		}

		private function findRoute (RequestInterface $request) : RouteMatch {


			$path = $this->normalizePath($request->path());

			$route_match = $this->tryHashed($request, $path );

			if ( ! $route_match->route() ) {

				$route_match = $this->tryAbsolute( $request, $path );

			}

			return $route_match;


		}

		private function tryAbsolute (RequestInterface $request, $url ) : RouteMatch {


			$route_info = $this->route_matcher->find( $request->getMethod(), $url );

			if ( $route_info[0] != Dispatcher::FOUND ) {

				return new RouteMatch(null, []);

			}

			/** @var Route $route */
			$route   = $route_info[1];
			$payload = $route_info[2];

			if ( ! $this->satisfiesCustomConditions( $route, $request ) ) {

				return new RouteMatch(null, []);

			}

			return new RouteMatch($route, $payload);

		}

		private function tryHashed ( RequestInterface $request, $url ) : RouteMatch {

			return $this->tryAbsolute($request, $this->hash($url));

		}

		private function normalizePath(string $path  ) : string {

			return rtrim( $path , '/' );

		}

	}
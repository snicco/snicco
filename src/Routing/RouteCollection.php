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
		 * A flattened array of all of the routes.
		 *
		 * @var Route[]
		 */
		private $all_routes = [];

		/**
		 * A look-up table of routes by their names.
		 *
		 * @var Route[]
		 */
		private $name_list = [];

		private $static_url_map = [];

		private $dynamic_url_map = [];

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

		private function addToCollection( Route $route ) {


			foreach ( $route->getMethods() as $method ) {

				$this->routes[ $method ][] = $route;

			}

			$this->all_routes[] = $route;


		}

		private function addLookups( Route $route ) {

			if ( $name = $route->getName() ) {
				$this->name_list[ $name ] = $route;
			}

		}

		public function match( RequestInterface $request ) : RouteMatch {

			[ $method, $path_info ] = [
				$request->getMethod(),
				rtrim( $request->getUri()->getPath(), '/' ),
			];

			$this->processRoutes( $method );

			$route_match = $this->findRoute($request, $method, $path_info);

			if ( ! $route_match->route() ) {

				return $route_match;

			}

			$route = $route_match->route();
			$payload = $route_match->payload();
			$condition_args = [];

			foreach ( $route->getCompiledConditions() as $compiled_condition ) {

				$args = $compiled_condition->getArguments( $request );

				$condition_args = array_merge( $condition_args, $args );

			}

			$payload = array_merge( $condition_args, $payload );

			$route->compileAction( $this->handler_factory );
			$request->setRoute( $route );

			return new RouteMatch($route, $payload);


		}


		public function processRoutes( string $method) {

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

		public function findByName( string $name ) : ?Route {

			$route = $this->nameList[ $name ] ?? null;

			if ( $route ) {

				return $route->compileConditions( $this->condition_factory );

			}

			$route = collect( $this->all_routes )->first( function ( Route $route ) use ( $name ) {

				return $route->getName() === $name;

			} );

			return ( $route ) ? $route->compileConditions( $this->condition_factory ) : null;

		}

		private function hash( string $path ) : string {

			$path = trim( $path, '/' );

			return md5( static::hash_key ) . '/' . $path;

		}

		private function findRoute (RequestInterface $request, $method, $path_info) : RouteMatch {

			$route_match = $this->tryStaticRoutes( $request, $method, $path_info );

			if ( ! $route_match->route() ) {

				$route_match = $this->tryDynamicRoutes($request, $method, $path_info);

			}

			return $route_match;

		}

		private function tryStaticRoutes( RequestInterface $request, $method, $path_info ) : RouteMatch {


			$route_info = $this->route_matcher->find(
				$method,
				$this->hash( $path_info )
			);

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

		private function tryDynamicRoutes( RequestInterface $request, string $method, string $path_info ) : RouteMatch {

			$route_info = $this->route_matcher->find( $method, $path_info );

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

	}
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

		public function _match( RequestInterface $request ) : array {

			[ $method, $path_info ] = [
				$request->getMethod(),
				rtrim( $request->getUri()->getPath(), '/' ),
			];

			$routes = Arr::get( $this->routes, $method, [] );

			$dispatcher = $this->toFastRouteDispatcher( $routes, $path_info );

			$map = $this->createCombinedUrlMap( $method, $path_info );

			$matching_route = null;
			$payload        = [];

			while ( $map[ $method ] ) {

				$registered_path = $this->reverseHash( $map, $method );

				$route_info = $dispatcher->dispatch( $method, $registered_path );

				if ( $route_info[0] != Dispatcher::FOUND ) {

					continue;

				}

				/** @var Route $route */
				$route = $route_info[1];

				if ( ! $this->satisfiesCustomConditions( $route, $request ) ) {

					continue;

				}

				$condition_args = [];

				foreach ( $route->getCompiledConditions() as $compiled_condition ) {

					$r = $compiled_condition->getArguments( $request );

					$condition_args = array_merge( $condition_args, $r );

				}

				$matching_route = $route;
				$payload        = $route_info[2];

				$payload = array_merge( $condition_args, $payload );

				unset( $this->static_url_map[ $method ][ $path_info ] );
				unset( $this->dynamic_url_map[ $method ][ $path_info ] );

				break;

			}

			if ( $matching_route ) {

				$matching_route->compileAction( $this->handler_factory );
				$request->setRoute( $matching_route );

			}

			return [ $matching_route, $payload ];

		}

		public function match( RequestInterface $request ) : array {

			[ $method, $path_info ] = [
				$request->getMethod(),
				rtrim( $request->getUri()->getPath(), '/' ),
			];

			$this->processRoutes( $method, $path_info );

			[ $route, $payload ]   = $this->tryStaticRoutes( $request, $method, $path_info );

			if ( ! $route ) {

				$route   = $this->tryDynamicRoutes( $request, $method, $path_info )[0];
				$payload = $this->tryDynamicRoutes( $request, $method, $path_info )[1];

			}

			if ( ! $route ) {

				return [ null, [] ];

			}

			$condition_args = [];

			foreach ( $route->getCompiledConditions() as $compiled_condition ) {

				$r = $compiled_condition->getArguments( $request );

				$condition_args = array_merge( $condition_args, $r );

			}

			$payload = array_merge( $condition_args, $payload );

			$route->compileAction( $this->handler_factory );
			$request->setRoute( $route );

			return [ $route, $payload ];


		}


		public function processRoutes( string $method, string $path_info ) {

			if ( $this->route_matcher->isCached() ) {

				return;

			}

			$routes = Arr::get( $this->routes, $method, [] );

			/** @var Route $route */
			foreach ( $routes as $route ) {

				$this->route_matcher->add( $method, $this->hashUrl( $route ), $route );

			}


		}



		private function reverseHash( array &$map, string $method ) {

			$hash = &$map[ $method ];

			$url = array_shift( $hash );

			return $url;


		}

		private function toFastRouteDispatcher( array $routes, string $path_info ) : Dispatcher {

			return simpleDispatcher( function ( RouteCollector $r ) use ( $routes, $path_info ) {

				foreach ( $routes as $route ) {

					$this->addToUrlMap( $route, $path_info );

					$r->addRoute(
						$route->getMethods(),
						$this->urlHash( $route ),
						$route

					);

				}

			} );

		}

		private function satisfiesCustomConditions( Route $route, RequestInterface $request ) : bool {

			$route->compileConditions( $this->condition_factory );

			return $route->matches( $request );

		}

		private function urlHash( Route $route ) : string {


			if ( UrlParser::isDynamicUrl( $route->url() ) ) {

				return $route->getCompiledUrl();

			}

			return $this->staticUrlHash( $route );


		}

		private function createCombinedUrlMap( string $method, string $path_info ) : array {

			$map = array_merge_recursive(
				Arr::get( $this->static_url_map, $method, [] ),
				Arr::get( $this->dynamic_url_map, $method, [] ),

			);

			$map = collect( $map )
				->flatten()
				->unique()
				->reject( function ( string $hashed_path ) use ( $path_info ) {

					return trim( Str::afterLast( $hashed_path, $path_info ), '/' ) !== '';

				} );

			return [ $method => $map->all() ];

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

		private function addToUrlMap( Route $route, string $path_info ) {

			foreach ( $route->getMethods() as $route_method ) {

				if ( UrlParser::isDynamicUrl( $route->url() ) ) {

					if ( ! in_array( $path_info, $this->dynamic_url_map[ $route_method ] ?? [] ) ) {

						$this->dynamic_url_map[ $route_method ][ $path_info ] = $path_info;

					}

					continue;

				}

				$url_hash = $this->staticUrlHash( $route );

				if ( ! in_array( $url_hash, $this->static_url_map[ $route_method ] ?? [] ) ) {

					$this->static_url_map[ $route_method ][ $path_info ][] = $url_hash;


				}


			}

		}

		private function staticUrlHash( Route $route ) : string {

			return '/' . spl_object_hash( $route ) . $route->url();

		}


		private function hashUrl( Route $route ) {

			if ( UrlParser::isDynamicUrl( $route->url() ) ) {

				return $route->getCompiledUrl();

			}

			return $this->hash( $route->url() );

		}

		private function hash( string $path ) {

			$path = trim( $path, '/' );

			return md5( static::hash_key ) . '/' . $path;

		}

		private function reverseHashUrl( string $path_info ) {


		}

		private function tryStaticRoutes( RequestInterface $request, $method, $path_info ) : array {

			$hashed_path = $this->hash( $path_info );

			$route_info = $this->route_matcher->findRoute( $method, $hashed_path );

			if ( $route_info[0] != Dispatcher::FOUND ) {

				return [ null, [] ];

			}

			/** @var Route $route */
			$route   = $route_info[1];
			$payload = $route_info[2];

			if ( ! $this->satisfiesCustomConditions( $route, $request ) ) {

				return [ null, [] ];

			}

			return [ $route, $payload ];


		}

		private function tryDynamicRoutes( RequestInterface $request, string $method, string $path_info ) : array {

			$route_info = $this->route_matcher->findRoute( $method, $path_info );

			if ( $route_info[0] != Dispatcher::FOUND ) {

				return [ null, [] ];

			}

			/** @var Route $route */
			$route   = $route_info[1];
			$payload = $route_info[2];

			if ( ! $this->satisfiesCustomConditions( $route, $request ) ) {

				return [ null, [] ];

			}

			return [ $route, $payload ];

		}

	}
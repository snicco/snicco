<?php


	namespace WPEmerge\Routing;

	use FastRoute\Dispatcher;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Support\Arr;

	use function FastRoute\simpleDispatcher;
	use function tad\WPBrowser\isDsnString;

	class RouteCollection {

		/** @var ConditionFactory */
		private $condition_factory;

		/** @var HandlerFactory */
		private $handler_factory;

		/**
		 * An array of the routes keyed by method.
		 *
		 * @var Route[]
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


		public function __construct( ConditionFactory $condition_factory, HandlerFactory $handler_factory ) {

			$this->condition_factory = $condition_factory;
			$this->handler_factory   = $handler_factory;

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

		/**
		 * @todo Find a way to not recompile shared conditions that were passed by
		 * @todo previous routes but the route in total didnt match.
		 */
		public function _match( RequestInterface $request ) {

			$routes = collect( Arr::get( $this->routes, $request->getMethod(), [] ) );

			$route = $routes
				->first( function ( Route $route ) use ( $request ) {

					$route->compileConditions( $this->condition_factory );

					return $route->matches( $request );

				} );

			if ( $route ) {

				$route->compileAction( $this->handler_factory );
				$request->setRoute( $route );

			}

			return $route;

		}

		public function match( RequestInterface $request ) : ?Route {


			[ $method, $path_info ] = [ $request->getMethod(), $request->getUri()->getPath() ];

			$routes = Arr::get( $this->routes, $method, [] );

			$dispatcher = $this->toFastRouteDispatcher( $routes, $path_info);

			$map = $this->createCombinedUrlMap($method);

			$matching_route = null;

			while ( $map[$method] ) {

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

				$matching_route = $route;

				break;

			}

			if ( $matching_route ) {

				$matching_route->compileAction( $this->handler_factory );
				$request->setRoute( $matching_route );

			}

			return $matching_route;

		}

		private function reverseHash( array &$map, string $method ) {

			$hash = &$map[ $method ];

			$url = array_shift( $hash );

			return $url;


		}

		private function toFastRouteDispatcher( array $routes , string $path_info ) : Dispatcher {

			return simpleDispatcher( function ( $r ) use ( $routes, $path_info ) {

				foreach ( $routes as $route ) {

					$this->createUrlMap( $route, $path_info );

					$r->addRoute(
						$route->getMethods(),
						$this->createUrlHash($route, $path_info ),
						$route
					);

				}

			} );

		}

		private function satisfiesCustomConditions( Route $route, RequestInterface $request ) : bool {

			$route->compileConditions( $this->condition_factory );

			return $route->matches( $request );

		}

		private function createUrlHash(  Route $route, string $path_info  ) : string {


				if ( UrlParser::isDynamicUrl( $url = $route->url() ) ) {

					return $path_info;

				}

				$url_hash = '/' . spl_object_hash( $route ) . $route->url();

				return $url_hash;



		}

		private function createCombinedUrlMap ( string $method ) : array {

			return [

				$method => array_merge(
					Arr::get($this->static_url_map, $method, [] ),
					Arr::get($this->dynamic_url_map, $method, [] ),
				)

			];

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

		private function createUrlMap( Route $route, string $path_info ) {

			foreach ( $route->getMethods() as $route_method ) {

				if ( UrlParser::isDynamicUrl( $route->url() ) ) {


					if ( ! in_array($path_info, $this->dynamic_url_map[$route_method] ?? [] )  ) {

						$this->dynamic_url_map[ $route_method ][] = $path_info;

					}

				}

				$url_hash = '/' . spl_object_hash( $route ) . $route->url();

				if ( ! in_array($url_hash, $this->static_url_map[$route_method] ?? [] ) ) {

					$this->static_url_map[$route_method][] = $url_hash;


				}


			}


		}

	}
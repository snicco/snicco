<?php


	namespace WPEmerge\Routing;

	use GuzzleHttp\Psr7\Request;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Support\Arr;

	use function FastRoute\simpleDispatcher;

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

				});

			if ( $route ) {

				$route->compileAction( $this->handler_factory );
				$request->setRoute( $route );

			}

			return $route;

		}

		public function match( RequestInterface $request ) {


			[$method, $path_info ] = [$request->getMethod(), $request->getUri()->getPath()];

			$routes = collect( Arr::get( $this->routes, $method , [] ) );

			// simpleDispatcher(function ($r) use ( $routes ) {
			//
			// 	foreach ( $routes as $route) {
			// 		$r->addRoute($route['method'], $route['uri'], $route['action']);
			// 	}
			//
			// });


			$route = $routes
				->first( function ( Route $route ) use ( $request ) {

					$route->compileConditions( $this->condition_factory );

					return $route->matches( $request );

				});

			if ( $route ) {

				$route->compileAction( $this->handler_factory );
				$request->setRoute( $route );

			}

			return $route;

		}

		public function findByName(string $name ) : ?Route {

			$route =  $this->nameList[$name] ?? null;

			if ( $route ) {

				return $route->compileConditions($this->condition_factory);

			}

			$route = collect( $this->all_routes )->first( function ( Route $route ) use ( $name ) {

				return $route->getName() === $name;

			} );

			return ( $route ) ? $route->compileConditions($this->condition_factory) : null;

		}

	}
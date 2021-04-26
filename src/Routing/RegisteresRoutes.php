<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\WPEmgereArr;

	/**
	 * Allow objects to have routes
	 */
	trait RegisteresRoutes {

		/**
		 * Array of registered routes
		 *
		 * @var RouteInterface[]
		 */
		protected $routes = [];

		/**
		 * Get routes.
		 *
		 * @codeCoverageIgnore
		 * @return RouteInterface[]
		 */
		public function getRoutes() {

			return $this->routes;
		}

		/**
		 * Add a route.
		 *
		 * @param  RouteInterface  $route
		 *
		 * @return void
		 */
		public function addRoute( RouteInterface $route ) {

			$routes = $this->getRoutes();
			$name   = $route->getAttribute( 'name' );

			if ( in_array( $route, $routes, true ) ) {
				throw new ConfigurationException( 'Attempted to register a route twice.' );
			}

			if ( $name !== '' ) {
				foreach ( $routes as $registered ) {
					if ( $name === $registered->getAttribute( 'name' ) ) {
						throw new ConfigurationException( "The route name \"$name\" is already registered." );
					}
				}
			}

			$this->routes[] = $route;
		}

		/**
		 * Remove a route.
		 *
		 * @param  RouteInterface  $route
		 *
		 * @return void
		 */
		public function removeRoute( RouteInterface $route ) {

			$routes = $this->getRoutes();

			$index = array_search( $route, $routes, true );

			if ( $index === false ) {
				return;
			}

			$this->routes = array_values( WPEmgereArr::except( $routes, $index ) );
		}

	}

<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Support\WPEmgereArr;

	class RouteRegistrar {


		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;

		/**
		 * @var array
		 */
		private $config;

		public function __construct( Router $router, array $config = [] ) {

			$this->router = $router;
			$this->config = $config;

		}

		public function loadRoutes() {

			if ( wp_doing_ajax() ) {

				$this->loadRoutesGroup( 'ajax' );

				return;
			}

			if ( is_admin() ) {

				$this->loadRoutesGroup( 'admin' );

				return;
			}

			$this->loadRoutesGroup( 'web' );

		}

		public function loadRouteFile( string $route_file ) {

			require $route_file;

		}

		private function loadRoutesGroup( string $group ) {


			$file       = WPEmgereArr::get( $this->config, 'routes.' . $group . '.definitions', '' );
			$attributes = WPEmgereArr::get( $this->config, 'routes.' . $group . '.attributes', [] );

			if ( empty( $file ) ) {
				return;
			}

			$middleware = WPEmgereArr::get( $attributes, 'middleware', [] );

			if ( ! in_array( $group, $middleware, true ) ) {

				$middleware = array_merge( [ $group ], $middleware );

			}

			$attributes['middleware'] = $middleware;

			$this->router->group($attributes, $file );


		}



	}
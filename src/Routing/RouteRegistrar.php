<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\WPEmgereArr;

	class RouteRegistrar {


		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;

		/**
		 * @var ApplicationConfig
		 */
		private $config;

		public function __construct( Router $router, ApplicationConfig $config) {

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

		public static function loadRouteFile( string $route_file ) {

			require $route_file;

		}

		private function loadRoutesGroup( string $group ) {

			$file = $this->config->get('routes.' . $group . '.definitions', '');
			$attributes = $this->config->get('routes.' . $group . '.attributes', []);

			if ( empty( $file ) ) {
				return;
			}

			$middleware = Arr::get( $attributes, 'middleware', [] );

			if ( ! in_array( $group, $middleware, true ) ) {

				$middleware = array_merge( [ $group ], $middleware );

			}

			$attributes['middleware'] = $middleware;

			$this->router->group( $attributes, $file );


		}



	}
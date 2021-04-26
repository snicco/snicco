<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Support\WPEmgereArr;

	class RouteRegistrar {


		/**
		 * @var \WPEmerge\Routing\RouteBlueprint
		 */
		private $route_blueprint;

		/**
		 * @var array
		 */
		private $config;

		public function __construct(RouteBlueprint $route_blueprint, array $config = [] ) {

			$this->route_blueprint = $route_blueprint;
			$this->config = $config;
		}

		/**
		 * Load route definition files depending on the current request.
		 *
		 * @return void
		 */
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


			$this->route_blueprint->attributes( $attributes )->group( $file );


		}

	}
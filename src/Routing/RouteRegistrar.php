<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\FilePath;

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

			$dir = FilePath::addTrailingSlash($this->config->get('routing.definitions',''));

			if ( $dir === '/' ) {
				return;
			}

			$file = FilePath::ending( $dir . $group ,'php');



			$this->router->group( ['middleware' => [$group]] , $file );


		}



	}
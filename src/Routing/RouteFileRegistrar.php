<?php


	namespace WPEmerge\Routing;

	class RouteFileRegistrar {


		public function __construct( Router $param ) {

		}

		public function register( $routes ) {

			require $routes;

		}


	}
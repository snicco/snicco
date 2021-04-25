<?php


	namespace WPEmerge\Csrf;

	use WPEmerge\Contracts\ServiceProviderInterface;


	class CsrfServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {

			$container[ WPEMERGE_CSRF_KEY ] = function () {

				return new Csrf();
			};

			$container[ CsrfMiddleware::class ] = function ( $c ) {

				return new CsrfMiddleware( $c[ WPEMERGE_CSRF_KEY ] );
			};


		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

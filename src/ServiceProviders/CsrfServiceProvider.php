<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Middleware\CsrfProtection;
	use WPEmerge\Session\Csrf;

	use const WPEMERGE_CSRF_KEY;

	class CsrfServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {

			$container[ WPEMERGE_CSRF_KEY ] = function () {

				return new Csrf();
			};

			$container[ CsrfProtection::class ] = function ( $c ) {

				return new CsrfProtection( $c[ WPEMERGE_CSRF_KEY ] );
			};


		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

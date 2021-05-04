<?php



	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Middleware\Authorize;
	use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Middleware\RedirectIfAuthenticated;

	use const WPEMERGE_RESPONSE_SERVICE_KEY;

	/**
	 * Provide middleware dependencies.
	 *
	 */
	class MiddlewareServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {


			$container[ RedirectIfAuthenticated::class ] = function ( $c ) {

				return new RedirectIfAuthenticated( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};

			$container[ Authenticate::class ] = function ( $c ) {

				return new Authenticate( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );

			};

			$container[ Authorize::class ] = function ( $c ) {

				return new Authorize( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};


		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

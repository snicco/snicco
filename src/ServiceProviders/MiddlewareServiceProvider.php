<?php



	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Middleware\UserCan;
	use WPEmerge\Middleware\UserLoggedIn;
	use WPEmerge\Middleware\UserLoggedOut;

	use const WPEMERGE_RESPONSE_SERVICE_KEY;

	/**
	 * Provide middleware dependencies.
	 *
	 */
	class MiddlewareServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {


			$container[ UserLoggedOut::class ] = function ( $c ) {

				return new UserLoggedOut( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};

			$container[ UserLoggedIn::class ] = function ( $c ) {

				return new UserLoggedIn( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );

			};

			$container[ UserCan::class ] = function ( $c ) {

				return new UserCan( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};


		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

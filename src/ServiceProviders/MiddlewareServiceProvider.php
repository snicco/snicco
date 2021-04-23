<?php



	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Middleware\UserCanMiddleware;
	use WPEmerge\Middleware\UserLoggedInMiddleware;
	use WPEmerge\Middleware\UserLoggedOutMiddleware;

	use const WPEMERGE_RESPONSE_SERVICE_KEY;

	/**
	 * Provide middleware dependencies.
	 *
	 */
	class MiddlewareServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {

			// $container[ SubstituteBindings::class ] = function ( $c ) {
			//
			// 	return new SubstituteBindings();
			//
			// };

			$container[ UserLoggedOutMiddleware::class ] = function ( $c ) {

				return new UserLoggedOutMiddleware( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};

			$container[ UserLoggedInMiddleware::class ] = function ( $c ) {

				return new UserLoggedInMiddleware( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );

			};

			$container[ UserCanMiddleware::class ] = function ( $c ) {

				return new UserCanMiddleware( $c[ WPEMERGE_RESPONSE_SERVICE_KEY ] );
			};
		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

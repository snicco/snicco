<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Flash\Flash;
	use WPEmerge\Flash\FlashMiddleware;


	class FlashServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {


			$container->singleton(WPEMERGE_FLASH_KEY, function ($c) {

				if ( isset( $c[ WPEMERGE_SESSION_KEY ] ) ) {

					$session = &$c[ WPEMERGE_SESSION_KEY ];

					return new Flash( $session );

				}

				if ( isset($_SESSION) ) {

					$session = &$_SESSION;

					return new Flash( $session );

				}


			});

			$container->singleton(FlashMiddleware::class, function($c) {

				return new FlashMiddleware( $c[ WPEMERGE_FLASH_KEY ] );

			} );

			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'flash', WPEMERGE_FLASH_KEY );
		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

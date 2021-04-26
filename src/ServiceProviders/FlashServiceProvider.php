<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Session\FlashStore;
	use WPEmerge\Middleware\Flash;


	class FlashServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {


			$container->singleton(WPEMERGE_FLASH_KEY, function ($c) {

				if ( isset( $c[ WPEMERGE_SESSION_KEY ] ) ) {

					$session = &$c[ WPEMERGE_SESSION_KEY ];

					return new FlashStore( $session );

				}

				if ( isset($_SESSION) ) {

					$session = &$_SESSION;

					return new FlashStore( $session );

				}


			});

			$container->singleton(Flash::class, function($c) {

				return new Flash( $c[ WPEMERGE_FLASH_KEY ] );

			} );

		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

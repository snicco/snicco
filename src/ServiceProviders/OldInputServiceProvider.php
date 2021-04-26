<?php


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Session\OldInputStore;
	use WPEmerge\Middleware\OldInput;


	/**
	 *
	 * Provide old input dependencies.
	 *
	 */
	class OldInputServiceProvider implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {

			$container->singleton(WPEMERGE_OLD_INPUT_KEY , function ( $c ) {

				return new OldInput( $c[ WPEMERGE_FLASH_KEY ] );

			});


			$container->singleton(OldInput::class , function ( $c ) {

				return new OldInput( $c[ WPEMERGE_OLD_INPUT_KEY ] );

			});





		}


		public function bootstrap( ContainerAdapter $container ) {

			// Nothing to bootstrap.

		}



	}
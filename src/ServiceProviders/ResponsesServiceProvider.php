<?php

	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Http\ResponseService;



	class ResponsesServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {


			$container->singleton(ResponseServiceInterface::class, function ( $container ) {

				return new ResponseService(
					$container[ WPEMERGE_REQUEST_KEY ],
					$container[ WPEMERGE_VIEW_SERVICE_KEY ]
				);

			});



		}

		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

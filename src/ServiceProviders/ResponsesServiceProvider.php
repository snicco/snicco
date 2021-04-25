<?php

	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Responses\ResponseService;

	use const WPEMERGE_APPLICATION_KEY;
	use const WPEMERGE_REQUEST_KEY;
	use const WPEMERGE_RESPONSE_SERVICE_KEY;
	use const WPEMERGE_VIEW_SERVICE_KEY;


	class ResponsesServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {


			$container->singleton(WPEMERGE_RESPONSE_SERVICE_KEY, function ( $container ) {

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

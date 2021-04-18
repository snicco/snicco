<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Requests\Request;

	use const WPEMERGE_REQUEST_KEY;

	/**
	 * Provide request dependencies.
	 *
	 */
	class RequestsServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {

			$container[ WPEMERGE_REQUEST_KEY ] = function () {

				return Request::fromGlobals();

			};

		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

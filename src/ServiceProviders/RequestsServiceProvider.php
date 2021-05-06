<?php


	namespace WPEmerge\ServiceProviders;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Http\Request;

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

			$container->singleton(ResponseInterface::class, function () {

				return null;

			});

		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

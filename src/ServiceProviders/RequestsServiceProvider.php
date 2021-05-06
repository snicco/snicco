<?php


	namespace WPEmerge\ServiceProviders;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Request;


	/**
	 * Provide request dependencies.
	 *
	 */
	class RequestsServiceProvider implements ServiceProviderInterface {


		public function register( $container ) {

			$container[ RequestInterface::class ] = function () {

				return Request::capture();

			};

			$container->singleton(ResponseInterface::class, function () {

				return null;

			});

		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

<?php

	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Http\ResponseFactory;

	class ResponsesServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {


			$container->singleton(ResponseFactoryInterface::class, function ($c) {

				return new ResponseFactory($c[ViewServiceInterface::class]);

			});



		}

		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}

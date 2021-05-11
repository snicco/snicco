<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Http\ResponseFactory;

	class ResponsesServiceProvider extends ServiceProvider {

		public function register() :void {


			$this->container->singleton(ResponseFactoryInterface::class, function () {

				return new ResponseFactory($this->container->make(ViewServiceInterface::class));

			});



		}

		public function bootstrap() :void  {
			// Nothing to bootstrap.
		}

	}

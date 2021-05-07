<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Http\Request;

	class RequestsServiceProvider extends ServiceProvider {


		public function register() : void {

			$this->container->singleton( RequestInterface::class, function () {


				$this->container->instance( RequestInterface::class, $request = Request::capture() );

				return $request;

			} );


		}


		public function bootstrap() : void {
			// Nothing to bootstrap.
		}

	}

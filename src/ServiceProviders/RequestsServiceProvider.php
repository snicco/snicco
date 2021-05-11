<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Http\Request;

	class RequestsServiceProvider extends ServiceProvider {


		public function register() : void {

			//
		}


		public function bootstrap() : void {
			// Nothing to bootstrap.
		}

	}

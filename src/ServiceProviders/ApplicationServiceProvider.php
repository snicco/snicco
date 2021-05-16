<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Facade\WordpressApi;

	class ApplicationServiceProvider extends ServiceProvider {


		public function register() : void {

		    $this->config->extend('always_run_middleware', false);


			$this->container->singleton( WordpressApi::class, function () {

				return new WordpressApi();

			} );


		}

		public function bootstrap() : void {



		}

	}

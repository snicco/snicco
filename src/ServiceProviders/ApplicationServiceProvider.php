<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Facade\WordpressApi;

	class ApplicationServiceProvider extends ServiceProvider {

		public const STRICT_MODE = 'strict_mode';

		public function register() : void {


			$this->config->extend( static::STRICT_MODE, false );


			$this->container->singleton( WordpressApi::class, function () {

				return new WordpressApi();

			} );


		}

		public function bootstrap() : void {



		}

	}

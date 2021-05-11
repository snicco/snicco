<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use test\Mockery\HasUnknownClassAsTypeHintOnMethod;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Exceptions\Exception;
	use WPEmerge\Support\FilePath;

	/**
	 * Provide application dependencies.
	 *
	 */
	class ApplicationServiceProvider extends ServiceProvider {

		public const STRICT_MODE = 'strict_mode';

		public function register() : void {


			$this->config->extend( static::STRICT_MODE, false );


		}


		public function bootstrap() : void {



		}

	}

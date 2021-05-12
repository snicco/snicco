<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WpFacade\WpFacade;

	/**
	 * Provide application dependencies.
	 *
	 */
	class ApplicationServiceProvider extends ServiceProvider {

		public const STRICT_MODE = 'strict_mode';

		public function register() : void {


			$this->config->extend( static::STRICT_MODE, false );

			WpFacade::setFacadeContainer($this->container);



		}


		public function bootstrap() : void {



		}

	}

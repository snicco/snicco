<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use Mockery;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Facade\WordpressApi;
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

			$this->container->singleton(WordpressApi::class, function () {

				if ( ! $this->config->get('testing.enabled', false ) ) {

					return new WordpressApi();

				}

				if ( $callable = $this->config->get('testing.callable', false ) ) {

					return $this->container->call($callable, [$this->container]);

				}

				return Mockery::mock(WordpressApi::class)->makePartial();


			});


		}


		public function bootstrap() : void {



		}

	}

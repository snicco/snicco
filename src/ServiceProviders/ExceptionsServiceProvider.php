<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProvider;

	use WPEmerge\Factories\ExceptionHandlerFactory;

	/**
	 * Provide exceptions dependencies.
	 *
	 */
	class ExceptionsServiceProvider extends ServiceProvider {



		public function register() : void {

			$this->config->extend('debug.enable', true );
			$this->config->extend('debug.pretty_errors', true );


			$this->container->singleton( ErrorHandlerInterface::class, function () {


				$request = $this->container->make( RequestInterface::class );

				return ( ( new ExceptionHandlerFactory(
					WP_DEBUG,
					$request->isAjax(),
					'phpstorm' ) )
				)->create();

			} );


		}


		public function bootstrap() : void {

			// Nothing to bootstrap.

		}

	}

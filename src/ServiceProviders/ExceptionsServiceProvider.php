<?php


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ServiceProvider;

	use WPEmerge\Factories\ExceptionHandlerFactory;
	use WPEmerge\Traits\ExtendsConfig;

	/**
	 * Provide exceptions dependencies.
	 *
	 */
	class ExceptionsServiceProvider extends ServiceProvider {


		use ExtendsConfig;

		public function register() : void {

			$this->extendConfig( $this->container, 'debug', [
				'enable'        => true,
				'pretty_errors' => true,
			] );

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

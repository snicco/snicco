<?php



	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;

	use WPEmerge\Factories\ExceptionHandlerFactory;
	use WPEmerge\Http\Request;
	use WPEmerge\Traits\ExtendsConfig;


	/**
	 * Provide exceptions dependencies.
	 *
	 */
	class ExceptionsServiceProvider implements ServiceProviderInterface {


		use ExtendsConfig;


		public function register( $container ) {

			$this->extendConfig( $container, 'debug', [
				'enable'        => true,
				'pretty_errors' => true,
			] );


			$container->singleton(ErrorHandlerInterface::class, function ($container) {

				/**
				 * @todo Replace with Container Request
				 * @todo Add Custom Datatables for current route to Whoops
				 */
				$ajax = Request::capture()->isAjax();

				return (( new ExceptionHandlerFactory(WP_DEBUG, $ajax, 'phpstorm')))
					->create();

			});



		}


		public function bootstrap( $container ) {

			// Nothing to bootstrap.

		}

	}

<?php



	namespace WPEmerge\ServiceProviders;

	use Whoops\Handler\PrettyPageHandler;
	use Whoops\Run;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Exceptions\ErrorHandler;
	use WPEmerge\Exceptions\Whoops\DebugDataProvider;

	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY;
	use const WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY;
	use const WPEMERGE_RESPONSE_SERVICE_KEY;

	/**
	 * Provide exceptions dependencies.
	 *
	 */
	class ExceptionsServiceProvider implements ServiceProviderInterface {


		use ExtendsConfigTrait;


		public function register( $container ) {

			$this->extendConfig( $container, 'debug', [
				'enable'        => true,
				'pretty_errors' => true,
			] );

			$container[ DebugDataProvider::class ] = function ( $container ) {

				return new DebugDataProvider( $container );

			};

			$container[ PrettyPageHandler::class ] = function ( $container ) {

				$handler = new PrettyPageHandler();
				$handler->addResourcePath( implode( DIRECTORY_SEPARATOR, [
					WPEMERGE_DIR,
					'src',
					'Exceptions',
					'Whoops',
				] ) );

				$handler->addDataTableCallback( 'WP Emerge: Route', function ( $inspector ) use ( $container ) {

					return $container[ DebugDataProvider::class ]->route( $inspector );
				} );

				return $handler;
			};

			$container[ Run::class ] = function ( $container ) {

				if ( ! class_exists( Run::class ) ) {
					return null;
				}

				$run = new Run();
				$run->allowQuit( false );

				$handler = $container[ PrettyPageHandler::class ];

				if ( $handler ) {
					$run->pushHandler( $handler );
				}

				return $run;
			};

			$container[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = function ( $container ) {

				$debug  = $container[ WPEMERGE_CONFIG_KEY ]['debug'];
				$whoops = $debug['pretty_errors'] ? $container[ Run::class ] : null;

				return new ErrorHandler( $container[ WPEMERGE_RESPONSE_SERVICE_KEY ], $whoops, $debug['enable'] );
			};

			$container[ WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY ] = function ( $container ) {

				$debug  = $container[ WPEMERGE_CONFIG_KEY ]['debug'];
				$whoops = $debug['pretty_errors'] ? $container[ Run::class ] : null;

				return new ErrorHandler( $container[ WPEMERGE_RESPONSE_SERVICE_KEY ], $whoops, $debug['enable'] );
			};
		}


		public function bootstrap( $container ) {

			// Nothing to bootstrap.

		}

	}

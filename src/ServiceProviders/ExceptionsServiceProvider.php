<?php



	namespace WPEmerge\ServiceProviders;

	use Whoops\Exception\Inspector;
	use Whoops\Handler\PrettyPageHandler;
	use Whoops\Run as WhoopsRun;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Exceptions\ErrorHandler;
	use WPEmerge\Exceptions\Whoops\DebugDataProvider;

	use WPEmerge\Factories\ExceptionHandlerFactory;
	use WPEmerge\Http\Request;
	use WPEmerge\Traits\ExtendsConfig;

	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY;
	use const WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY;
	use const WPEMERGE_RESPONSE_SERVICE_KEY;

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

				$ajax = Request::capture()->isAjax();

				return (( new ExceptionHandlerFactory(WP_DEBUG,$ajax, 'phpstorm')))
					->create(
						$container[ResponseServiceInterface::class]
					);

			});

			// $container[ PrettyPageHandler::class ] = function ( $container ) {
			//
			// 	$handler = new PrettyPageHandler();
			// 	$handler->setEditor('phpstorm');
			// 	$handler->addResourcePath( implode( DIRECTORY_SEPARATOR, [
			// 		WPEMERGE_DIR,
			// 		'src',
			// 		'Exceptions',
			// 		'Whoops',
			// 	] ) );
			//
			// 	/** @todo fix this. */
			// 	// $handler->addDataTableCallback( 'Current route', function ( Inspector $inspector ) use ( $container ) {
			// 	//
			// 	// 	return (( new DebugDataProvider() ))->route( $inspector, $container[RequestInterface::class]->route() );
			// 	//
			// 	// } );
			// 	return $handler;
			//
			// };
			//
			// $container[ WhoopsRun::class ] = function ( $container ) {
			//
			// 	if ( ! class_exists( WhoopsRun::class ) ) {
			// 		return null;
			// 	}
			//
			// 	$run = new WhoopsRun();
			// 	$run->allowQuit( false );
			// 	$run->writeToOutput(false);
			//
			// 	$handler = $container[ PrettyPageHandler::class ];
			//
			// 	if ( $handler ) {
			// 		$run->pushHandler( $handler );
			// 	}
			//
			// 	return $run;
			// };
			//
			// $container[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = function ( $container ) {
			//
			// 	$debug  = $container[ WPEMERGE_CONFIG_KEY ]['debug'];
			// 	$whoops = $debug['pretty_errors'] ? $container[ WhoopsRun::class ] : null;
			//
			// 	return new ErrorHandler( $container[ WPEMERGE_RESPONSE_SERVICE_KEY ], $whoops, $debug['enable'] );
			// };
			//
			// $container[ WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY ] = function ( $container ) {
			//
			// 	$debug  = $container[ WPEMERGE_CONFIG_KEY ]['debug'];
			// 	$whoops = $debug['pretty_errors'] ? $container[ WhoopsRun::class ] : null;
			//
			// 	return new ErrorHandler( $container[ WPEMERGE_RESPONSE_SERVICE_KEY ], $whoops, $debug['enable'] );
			// };

		}


		public function bootstrap( $container ) {

			// Nothing to bootstrap.

		}

	}

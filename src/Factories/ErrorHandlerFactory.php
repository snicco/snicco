<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use Contracts\ContainerAdapter;
	use Psr\Log\LoggerInterface;
	use Psr\Log\NullLogger;
	use Whoops\Handler\JsonResponseHandler;
	use Whoops\Handler\PrettyPageHandler;
	use Whoops\Run;
	use Whoops\RunInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Exceptions\ProductionErrorHandler;

	class ErrorHandlerFactory {

		const ALLOWED_EDITORS = [
			'emacs',
			'idea',
			'macvim',
			'phpstorm',
			'sublime',
			'textmate',
			'xdebug',
			'vscode',
			'atom',
			'espresso',
		];

		/**
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public static function make( ContainerAdapter $container, bool $is_debug, bool $is_ajax_request, string $editor = null ) {

			if ( ! $is_debug ) {

				return static::createProductionHandler( $container, $is_ajax_request );

			}

			[ $whoops, $pretty_page_handler ] = static::createWhoops( $container );

			if ( $is_ajax_request ) {

				static::prependJsonHandler( $whoops );

			}

			if ( $editor ) {

				static::setEditor($pretty_page_handler, $editor);

			}

			return new DebugErrorHandler( $whoops );

		}

		private static function createProductionHandler( ContainerAdapter $container, bool $is_ajax ) : ProductionErrorHandler {

			$logger = $container->offsetExists( LoggerInterface::class )
				? $container->make( LoggerInterface::class )
				: new NullLogger();

			$class = $container->make(ProductionErrorHandler::class);

			return new $class($container, $logger, $is_ajax);

		}

		private static function prependJsonHandler( Run $whoops ) {

			$json_handler = new JsonResponseHandler();
			$json_handler->addTraceToOutput( true );
			$whoops->prependHandler( $json_handler );

		}

		private static function createWhoops( ContainerAdapter $container ) : array {

			$whoops              = new Run();
			$pretty_page_handler = new PrettyPageHandler();
			$pretty_page_handler->handleUnconditionally( true );

			$whoops->appendHandler( $pretty_page_handler );
			$whoops->allowQuit( false );
			$whoops->writeToOutput( true );

			$container->instance( RunInterface::class, $whoops );
			$container->instance( PrettyPageHandler::class, $pretty_page_handler );

			return [ $whoops, $pretty_page_handler ];

		}

		private static function setEditor ( PrettyPageHandler $handler , string $editor ) {

			if ( ! in_array( $editor, static::ALLOWED_EDITORS ) ) {

				throw new ConfigurationException(
					'The editor: ' . $editor . ' is not supported by Whoops.'
				);

			}

			$handler->setEditor( $editor );

		}

	}
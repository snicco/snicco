<?php


	namespace WPEmerge\Factories;

	use Whoops\Handler\JsonResponseHandler;
	use Whoops\Handler\PrettyPageHandler;
	use Whoops\Run;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Exceptions\ProductionErrorHandler;

	class ExceptionHandlerFactory {

		const allowed_editors = [

			'emacs',
			'idea',
			'macvim',
			'phpstorm',
			'sublime',
			'textmate',
			'xdebug',
			'vscode',
			'atom',
			'espresso'

		];

		/**
		 * @var bool
		 */
		private $is_debug;
		/**
		 * @var bool
		 */
		private $is_ajax;

		/**
		 * @var string|null
		 */
		private $code_editor;

		public function __construct( bool $is_debug = false, bool $is_ajax = false, string $code_editor = null  ) {

			$this->is_debug = $is_debug;
			$this->is_ajax = $is_ajax;
			$this->code_editor = $code_editor;

		}

		public function create ( ResponseServiceInterface $response_service ) :ErrorHandlerInterface {

			if ( ! $this->is_debug ) {

				return new ProductionErrorHandler();

			}

			$whoops = new Run();
			$pretty_page_handler = new PrettyPageHandler();
			$pretty_page_handler->handleUnconditionally(true);


			if ( $this->is_ajax ) {

				$json_handler = new JsonResponseHandler();
				$json_handler->addTraceToOutput(true);
				$whoops->appendHandler($json_handler);

			}

			if ( $this->code_editor ) {

				if ( ! in_array($this->code_editor , self::allowed_editors ) ) {

					throw new ConfigurationException(
						'The editor: ' . $this->code_editor . ' is not supported by Whoops.'
					);

				}

				$pretty_page_handler->setEditor($this->code_editor);

			}

			$whoops->appendHandler($pretty_page_handler);
			$whoops->allowQuit(false);
			$whoops->writeToOutput(false);


			return new DebugErrorHandler($whoops, $this->is_ajax);

		}

	}
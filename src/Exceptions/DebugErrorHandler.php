<?php


	namespace WPEmerge\Exceptions;

	use Throwable;
	use Whoops\RunInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;

	class DebugErrorHandler implements ErrorHandlerInterface {


		/** @var \Whoops\RunInterface */
		private $whoops;


		public function __construct(  RunInterface $whoops ) {

			$this->whoops           = $whoops;

		}


		public function register() {
			// TODO: Implement register() method.
		}

		public function unregister() {
			// TODO: Implement unregister() method.
		}

		public function transformToResponse( RequestInterface $request, Throwable $exception ) {

			$method = RunInterface::EXCEPTION_HANDLER;

			$this->whoops->{$method}($exception);


		}

	}
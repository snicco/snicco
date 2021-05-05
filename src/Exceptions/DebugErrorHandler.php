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

			$this->whoops = $whoops;

		}


		public function register() {

			$this->whoops->register();

		}

		public function unregister() {

			$this->whoops->unregister();

		}

		public function transformToResponse( RequestInterface $request, Throwable $exception ) {

			$method = RunInterface::EXCEPTION_HANDLER;

			$this->whoops->{$method}($exception);


		}

	}
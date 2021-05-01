<?php


	namespace Tests\stubs;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use Exception as PhpException;
	use WPEmerge\Contracts\RequestInterface;

	class IntegrationTestErrorHandler implements ErrorHandlerInterface {

		public function register() {

			//

		}

		public function unregister() {

			//

		}

		/**
		 * @throws \Exception
		 */
		public function getResponse( RequestInterface $request, \Throwable $exception ) {

			throw $exception;

		}

	}
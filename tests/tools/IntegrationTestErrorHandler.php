<?php


	namespace WPEmergeTestTools;

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
		public function getResponse( RequestInterface $request, PhpException $exception ) {

			throw $exception;

		}

	}
<?php


	namespace Tests\stubs;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use Exception as PhpException;
	use WPEmerge\Contracts\RequestInterface;

	class TestErrorHandler implements ErrorHandlerInterface {

		const bypass_messsage = 'FORCEDEXCEPTION';

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

			if ( $exception->getMessage() !== self::bypass_messsage ) {

				throw $exception;

			}

			return new TestResponse($request);

		}

	}
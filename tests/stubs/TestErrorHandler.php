<?php


	namespace Tests\stubs;

	use Psr\Http\Message\ResponseInterface;
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
		public function transformToResponse( RequestInterface $request, \Throwable $exception ) :ResponseInterface {

			if ( $exception->getMessage() !== self::bypass_messsage ) {

				throw $exception;

			}

			return new TestResponse($request);

		}

	}
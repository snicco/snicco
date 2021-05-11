<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;

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
		public function transformToResponse( \Throwable $exception, ?RequestInterface $request = null ) :ResponseInterface {

			throw $exception;


		}

		public function writeToOutput( bool $false = false ) : void {
			//
		}

		public function allowQuit( bool $false = false ) : void {
			//
		}

		public function isRegistered() : bool {

			return true;

		}

	}
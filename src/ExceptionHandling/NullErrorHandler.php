<?php


	declare( strict_types = 1 );


	namespace Snicco\ExceptionHandling;

	use Throwable;
    use Snicco\Contracts\ErrorHandlerInterface;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;

    class NullErrorHandler implements ErrorHandlerInterface {


		public function register() {
			//
		}

		public function unregister() {
			//
		}

		public function transformToResponse( \Throwable $exception, Request $request) : ?Response {

			throw $exception;

		}

        public function unrecoverable(Throwable $exception)
        {
            throw $exception;
        }

    }
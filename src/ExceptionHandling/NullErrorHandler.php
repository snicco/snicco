<?php


	declare( strict_types = 1 );


	namespace BetterWP\ExceptionHandling;

	use Throwable;
    use BetterWP\Contracts\ErrorHandlerInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

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
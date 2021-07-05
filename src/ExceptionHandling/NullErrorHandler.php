<?php


	declare( strict_types = 1 );


	namespace WPMvc\ExceptionHandling;

	use Throwable;
    use WPMvc\Contracts\ErrorHandlerInterface;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Psr7\Response;

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
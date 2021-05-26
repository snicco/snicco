<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

	use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Http\Psr7\Response;

    class NullErrorHandler implements ErrorHandlerInterface {


		public function register() {
			//
		}

		public function unregister() {
			//
		}

		public function transformToResponse( \Throwable $exception ) : ?Response {

			throw $exception;

		}

        public function unrecoverable(Throwable $exception)
        {
           $this->transformToResponse($exception);
        }

    }
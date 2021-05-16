<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

	use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Http\Response;

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

	}
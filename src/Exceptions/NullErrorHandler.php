<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Exceptions;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;

	class NullErrorHandler implements ErrorHandlerInterface {


		public function register() {
			//
		}

		public function unregister() {
			//
		}

		public function transformToResponse( \Throwable $exception, ?RequestInterface $request = null ) : ?ResponseInterface {

			throw $exception;

		}

	}
<?php


	namespace WPEmerge\Exceptions;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;

	class AjaxDebugErrorHandler implements ErrorHandlerInterface {

		/**
		 * AjaxDebugErrorHandler constructor.
		 */
		public function __construct() {
		}

		public function register() {
			// TODO: Implement register() method.
		}

		public function unregister() {
			// TODO: Implement unregister() method.
		}

		public function transformToResponse( RequestInterface $request, \Throwable $exception ) {
			// TODO: Implement transformToResponse() method.
		}

	}
<?php


	namespace WPEmerge\Contracts;

	use Exception as PhpException;
	use Psr\Http\Message\ResponseInterface;

	interface ErrorHandlerInterface {

		/**
		 * Register any necessary error, exception and shutdown handlers.
		 *
		 * @return void
		 */
		public function register();

		/**
		 * Unregister any registered error, exception and shutdown handlers.
		 *
		 * @return void
		 */
		public function unregister();

		/**
		 * Get a response representing the specified exception.
		 *
		 * @param  RequestInterface  $request
		 * @param  PhpException  $exception
		 *
		 * @return ResponseInterface
		 */
		public function getResponse( RequestInterface $request, PhpException $exception );

	}

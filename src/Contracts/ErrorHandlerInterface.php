<?php


	namespace WPEmerge\Contracts;


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
		 * @param  \Throwable  $exception
		 *
		 * @return ResponseInterface
		 */
		public function transformToResponse( RequestInterface $request, \Throwable $exception ) :ResponseInterface;

	}

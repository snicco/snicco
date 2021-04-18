<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Helpers\Handler;

	/**
	 * Describes how a request is handled.
	 */
	trait ReadsHandlerMiddlewareTrait {


		/**
		 * Get middleware registered with the given handler.
		 *
		 * @param  Handler  $handler
		 *
		 * @return string[]
		 */
		protected function getControllerMiddleware( Handler $handler ) : array {

			return $handler->controllerMiddleware();

		}

	}

<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Contracts\HasControllerMiddlewareInterface;
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
		 * @throws \WPEmerge\Exceptions\ClassNotFoundException
		 */
		protected function getControllerMiddleware( Handler $handler ) : array {

			$instance = $handler->make();

			if ( ! $instance instanceof HasControllerMiddlewareInterface ) {
				return [];
			}

			return $instance->getMiddleware( $handler->get()['method'] );

		}

	}

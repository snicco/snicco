<?php


	namespace WPEmerge\Middleware;

	use Closure;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;

	/**
	 * Executes middleware.
	 */
	trait ExecutesMiddlewareTrait {



		/**
		 * Execute an array of middleware recursively (last in, first out).
		 *
		 * @param  string[][]  $middleware
		 * @param  RequestInterface  $request
		 * @param  \Closure  $next_handler
		 *
		 * @return ResponseInterface
		 */
		private function executeMiddleware( $middleware, RequestInterface $request, Closure $next_handler )  {

			$top_middleware = array_shift( $middleware );

			if ( $top_middleware === null ) {
				return $next_handler( $request );
			}

			$top_middleware_next = function ( $request ) use ( $middleware, $next_handler ) {

				return $this->executeMiddleware( $middleware, $request, $next_handler );

			};


			$middleware_instance = $this->factory->make($top_middleware[0]);

			$arguments = array_merge(
				[ $request, $top_middleware_next ],
				$this->withPassedParameters($top_middleware)
			);

			return call_user_func_array( [ $middleware_instance, 'handle' ], $arguments );

		}


		/**
		 *
		 * Capture the passed arguments in the middleware
		 * that were specified in ->middleware(name:condition1:condition2)
		 *
		 * @param  array  $top_middleware
		 *
		 * @return array
		 */
		private function withPassedParameters(array $top_middleware) : array {

			return array_slice( $top_middleware, 1 );

		}

	}

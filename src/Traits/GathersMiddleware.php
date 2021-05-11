<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;

	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\Arr;

	trait GathersMiddleware {


		/**
		 * Sort array of fully qualified middleware class names by priority in ascending order.
		 *
		 * @param  string[]  $middleware
		 *
		 * @return array
		 */
		public function sortMiddleware( array $middleware ) : array {

			$sorted = $middleware;

			usort( $sorted, function ( $a, $b ) use ( $middleware ) {

				$a_priority = $this->getMiddlewarePriorityForMiddleware( $a );
				$b_priority = $this->getMiddlewarePriorityForMiddleware( $b );
				$priority   = $b_priority - $a_priority;

				if ( $priority !== 0 ) {
					return $priority;
				}

				// Keep relative order from original array.
				return array_search( $a, $middleware ) - array_search( $b, $middleware );
			} );

			return array_values( $sorted );
		}

		/**
		 * Filter array of middleware into a unique set.
		 *
		 * @param  array[]  $middleware
		 *
		 * @return string[]
		 */
		public function uniqueMiddleware( array $middleware ) : array {

			return array_values( array_unique( $middleware, SORT_REGULAR ) );

		}

		/**
		 * Expand array of middleware into an array of fully qualified class names.
		 *
		 * @param  string[]  $middleware
		 *
		 * @return array[]
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function expandMiddleware( array $middleware ) : array {

			$classes = [];

			foreach ( $middleware as $item ) {
				$classes = array_merge(
					$classes,
					$this->expandMiddlewareMolecule( $item )
				);
			}

			return $classes;
		}


		public function mergeGlobalMiddleware ( array $middleware ) : array {

			$global = $this->middleware_groups['global'] ?? [];

			$this->pushGlobalMiddlewarePriority($global);

			return array_merge( $global, $middleware );

		}



		/**
		 * Expand a middleware group into an array of fully qualified class names.
		 *
		 * @param  string  $group
		 *
		 * @return array[]
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function expandMiddlewareGroup( string $group ) : array {

			$middleware_in_group = $this->middleware_groups[ $group ];

			return $this->expandMiddleware( $middleware_in_group );

		}

		/**
		 * Expand middleware into an array of fully qualified class names and any companion
		 * arguments.
		 *
		 * @param  string  $middleware
		 *
		 * @return array[]
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function expandMiddlewareMolecule( string $middleware ) : array {

			$pieces = explode( ':', $middleware, 2 );

			if ( count( $pieces ) > 1 ) {
				return [ array_merge( [ $this->expandMiddlewareAtom( $pieces[0] ) ], explode( ',', $pieces[1] ) ) ];
			}

			if ( isset( $this->middleware_groups[ $middleware ] ) ) {
				return $this->expandMiddlewareGroup( $middleware );
			}

			return [ [ $this->expandMiddlewareAtom( $middleware ) ] ];
		}

		/**
		 * Expand a single middleware a fully qualified class name.
		 *
		 * @param  string  $middleware
		 *
		 * @return string
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function expandMiddlewareAtom( string $middleware ) : string {

			if ( isset( $this->route_middleware_aliases[ $middleware ] ) ) {
				return $this->route_middleware_aliases[ $middleware ];
			}

			if ( class_exists( $middleware ) ) {
				return $middleware;
			}

			throw new ConfigurationException( 'Unknown middleware [' . $middleware . '] used.' );
		}


		private function pushGlobalMiddlewarePriority (array $global_middleware)  {


			$filtered_globals = collect(($this->middleware_priority))
				->reject(function ($middleware) use ( $global_middleware ) {

					return Arr::isValue($middleware, $global_middleware);

			});

			$this->middleware_priority = collect($global_middleware)
				->merge($filtered_globals)
				->all();

		}

		/**
		 * Get priority for a specific middleware.
		 * This is in reverse compared to definition order.
		 * Middleware with unspecified priority will yield -1.
		 *
		 * @param  string|array  $middleware
		 *
		 * @return integer
		 */
		private function getMiddlewarePriorityForMiddleware( $middleware ) : int {

			if ( is_array( $middleware ) ) {
				$middleware = $middleware[0];
			}

			$increasing_priority = array_reverse( $this->middleware_priority );
			$priority            = array_search( $middleware, $increasing_priority );

			return $priority !== false ? (int) $priority : - 1;
		}



	}

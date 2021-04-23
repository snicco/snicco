<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Exceptions\ConfigurationException;

	trait HasMiddlewareDefinitionsTrait {


		/**
		 * Filter array of middleware into a unique set.
		 *
		 * @param  array[]  $middleware
		 *
		 * @return string[]
		 */
		private function uniqueMiddleware( $middleware ) {

			return array_values( array_unique( $middleware, SORT_REGULAR ) );
		}


		/**
		 * Expand array of middleware into an array of fully qualified class names.
		 *
		 * @param  string[]  $middleware
		 *
		 * @return array[]
		 */
		private function expandMiddleware( $middleware ) : array {

			$classes = [];

			foreach ( $middleware as $item ) {
				$classes = array_merge(
					$classes,
					$this->expandMiddlewareMolecule( $item )
				);
			}

			return $classes;
		}

		/**
		 * Expand a middleware group into an array of fully qualified class names.
		 *
		 * @param  string  $group
		 *
		 * @return array[]
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function expandMiddlewareGroup( $group ) : array {

			if ( ! isset( $this->middleware_groups[ $group ] ) ) {
				throw new ConfigurationException( 'Unknown middleware group "' . $group . '" used.' );
			}

			$middleware = $this->middleware_groups[ $group ];

			return $this->expandMiddleware( $middleware );

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
		private function expandMiddlewareMolecule( $middleware ) : array {

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
		private function expandMiddlewareAtom( $middleware ) : string {

			if ( isset( $this->middleware[ $middleware ] ) ) {
				return $this->middleware[ $middleware ];
			}

			if ( class_exists( $middleware ) ) {
				return $middleware;
			}

			throw new ConfigurationException( 'Unknown middleware "' . $middleware . '" used.' );
		}

		private function mergeGlobalMiddleware ( array $middleware ) {

			$global = $this->middleware_groups['global'] ?? [];

			return array_merge($global, $middleware );

		}

	}

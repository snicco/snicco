<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Exceptions\ConfigurationException;

	trait HasMiddlewareDefinitionsTrait {

		/**
		 * Middleware available to the application.
		 *
		 * @var array<string, string>
		 */
		protected $middleware = [];

		/**
		 * Middleware groups.
		 *
		 * @var array<string, string[]>
		 */
		protected $middleware_groups = [];

		/**
		 * Middleware groups that should have the 'wpemerge' and 'global' groups prepended to them.
		 *
		 * @var string[]
		 */
		protected $prepend_special_groups_to = [
			'web',
			'admin',
			'ajax',
		];

		/**
		 * Register middleware.
		 *
		 *
		 * @param  array<string, string>  $middleware
		 *
		 * @return void
		 */
		public function setMiddleware( $middleware ) {

			$this->middleware = $middleware;
		}

		/**
		 * Register middleware groups.
		 *
		 *
		 * @param  array<string, string[]>  $middleware_groups
		 *
		 * @return void
		 */
		public function setMiddlewareGroups( $middleware_groups ) {

			$this->middleware_groups = $middleware_groups;
		}

		/**
		 * Filter array of middleware into a unique set.
		 *
		 * @param  array[]  $middleware
		 *
		 * @return string[]
		 */
		public function uniqueMiddleware( $middleware ) {

			return array_values( array_unique( $middleware, SORT_REGULAR ) );
		}

		/**
		 * Expand array of middleware into an array of fully qualified class names.
		 *
		 * @param  string[]  $middleware
		 *
		 * @return array[]
		 */
		public function expandMiddleware( $middleware ) : array {

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
		public function expandMiddlewareGroup( $group ) : array {

			if ( ! isset( $this->middleware_groups[ $group ] ) ) {
				throw new ConfigurationException( 'Unknown middleware group "' . $group . '" used.' );
			}

			$middleware = $this->middleware_groups[ $group ];

			if ( in_array( $group, $this->prepend_special_groups_to, true ) ) {
				$middleware = array_merge( [ 'wpemerge', 'global' ], $middleware );
			}

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
		public function expandMiddlewareMolecule( $middleware ) : array {

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
		public function expandMiddlewareAtom( $middleware ) : string {

			if ( isset( $this->middleware[ $middleware ] ) ) {
				return $this->middleware[ $middleware ];
			}

			if ( class_exists( $middleware ) ) {
				return $middleware;
			}

			throw new ConfigurationException( 'Unknown middleware "' . $middleware . '" used.' );
		}

	}

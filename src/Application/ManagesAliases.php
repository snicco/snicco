<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use Closure;
	use BadMethodCallException;
	use WPEmerge\Support\WPEmgereArr;


	trait ManagesAliases {



		/**
		 * Resolve a dependency from the IoC container while taking
		 * aliases into account.
		 *
		 * @param  string  $key
		 *
		 * @return mixed|null
		 */
		abstract public function resolve( string $key );


		/** @var array<string, array> */
		private $aliases = [];


		public function hasAlias( string $alias ) : bool {

			return isset( $this->aliases[ $alias ] );
		}


		public function getAlias( string $alias ) : ?array {

			if ( ! $this->hasAlias( $alias ) ) {
				return null;
			}

			return $this->aliases[ $alias ];
		}

		/**
		 * Register an alias.
		 *
		 * @param  array<string, mixed>  $alias
		 *
		 */
		private function setAlias( array $alias ) :void {

			$name = WPEmgereArr::get( $alias, 'name' );

			$this->aliases[ $name ] = [
				'name'   => $name,
				'target' => WPEmgereArr::get( $alias, 'target' ),
				'method' => WPEmgereArr::get( $alias, 'method', '' ),
			];
		}

		/**
		 * Register an alias.
		 * Identical to setAlias but with a more user-friendly interface.
		 *
		 * @param  string  $alias
		 * @param  string|Closure  $target
		 * @param  string  $method
		 *
		 * @return void
		 */
		public function alias( string $alias, $target, $method = '' ) {

			$this->setAlias( [
				'name'   => $alias,
				'target' => $target,
				'method' => $method,
			] );
		}

		/**
		 * Call alias if registered.
		 *
		 * @param  string  $method
		 * @param  array  $parameters
		 *
		 * @return mixed
		 */
		public function __call( string $method, array $parameters ) {

			if ( method_exists($this, $method) ) {

				return call_user_func_array([$this, $method], $parameters);

			}

			if ( ! $this->hasAlias( $method ) ) {

				throw new BadMethodCallException( 'Method: ' . $method . ' does not exist.');

			}

			$alias = $this->aliases[ $method ];

			if ( $alias['target'] instanceof Closure ) {
				return call_user_func_array( $alias['target']->bindTo( $this, static::class ), $parameters );
			}

			$target = $this->resolve( $alias['target'] );

			if ( ! empty( $alias['method'] ) ) {
				return call_user_func_array( [ $target, $alias['method'] ], $parameters );
			}

			return $target;
		}



	}

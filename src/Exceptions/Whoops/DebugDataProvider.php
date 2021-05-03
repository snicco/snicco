<?php


	namespace WPEmerge\Exceptions\Whoops;

	use Whoops\Exception\Inspector;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteCondition;

	/**
	 * Provide debug data for usage with \Whoops\Handler\PrettyPageHandler.
	 *
	 */
	class DebugDataProvider {


		/**
		 * Convert a value to a scalar representation.
		 *
		 * @param  mixed  $value
		 *
		 * @return mixed
		 */
		public function toScalar( $value ) {

			$type = gettype( $value );

			if ( ! is_scalar( $value ) ) {
				$value = '(' . $type . ')' . ( $type === 'object' ? ' ' . get_class( $value ) : '' );
			}

			return $value;
		}

		/**
		 * Return printable data about the current route to Whoops
		 *
		 * @return array<string, mixed>
		 */
		public function route( Inspector $inspector, RouteCondition $route = null ) : array {

			if ( ! $route ) {
				return [];
			}

			$attributes = [];

			foreach ( $route->getAttributes() as $attribute => $value ) {
				// Only convert the first level of an array to scalar for simplicity.
				if ( is_array( $value ) ) {
					$value = '[' . implode( ', ', array_map( [
							$this,
							'toScalar',
						], $value ) ) . ']';
				} else {
					$value = $this->toScalar( $value );
				}

				$attributes[ $attribute ] = $value;
			}

			return $attributes;
		}

	}

<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use Illuminate\Support\Reflector;
	use ReflectionFunction;
	use ReflectionMethod;

	class RouteSignatureParameters {

		/**
		 * Extract the route action's signature parameters.
		 *
		 * @param  array|\Closure
		 *
		 * @return \ReflectionParameter[]
		 * @throws \ReflectionException
		 */
		public static function fromCallable( $callable ) : array {

			return is_array( $callable )
				? static::fromClassMethod( $callable )
				: ( new ReflectionFunction( $callable ) )->getParameters();


		}

		/**
		 * Get the parameters for the given class / method by string.
		 *
		 * @param  array  $callable
		 *
		 * @return array
		 * @throws \ReflectionException
		 */
		private static function fromClassMethod( array $callable ) : array {

			[ $class, $method ] = [ $callable[0], $callable[1] ];

			if ( ! Reflector::isCallable( $class, $method ) ) {
				return [];
			}

			return ( new ReflectionMethod( $class, $method ) )->getParameters();
		}

	}
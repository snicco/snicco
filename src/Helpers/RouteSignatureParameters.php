<?php


	namespace WPEmerge\Helpers;

	use Illuminate\Support\Reflector;
	use WPEmerge\Support\Str;
	use ReflectionFunction;
	use ReflectionMethod;

	class RouteSignatureParameters {

		/**
		 * Extract the route action's signature parameters.
		 *
		 * @param  \WPEmerge\Helpers\Handler  $handler
		 *
		 * @return \ReflectionParameter[]
		 * @throws \ReflectionException
		 * @throws \WPEmerge\Exceptions\ClassNotFoundException
		 */
		public static function fromCallable( Handler $handler) : array {


			return is_string( $callable = $handler->fqnCallable() )
				? static::fromClassMethodString( $callable )
				: ( new ReflectionFunction( $callable ) )->getParameters();


		}

		/**
		 * Get the parameters for the given class / method by string.
		 *
		 * @param  string  $callable
		 *
		 * @return array
		 * @throws \ReflectionException
		 */
		private static function fromClassMethodString( string $callable ) : array {

			[ $class, $method ] = Str::parseCallback( $callable );

			if ( ! method_exists( $class, $method ) && Reflector::isCallable( $class, $method ) ) {
				return [];
			}

			return ( new ReflectionMethod( $class, $method ) )->getParameters();
		}

	}
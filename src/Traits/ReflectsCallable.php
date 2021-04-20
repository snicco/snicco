<?php


	namespace WPEmerge\Traits;

	use Closure;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Str;
	use ReflectionClass;
	use ReflectionFunction;
	use ReflectionMethod;

	trait ReflectsCallable {


		/**
		 * @param array|Closure $callback
		 * @param string $default_method
		 *
		 * @return \ReflectionFunction|\ReflectionMethod
		 * @throws \ReflectionException
		 */
		private function getCallReflector( $callback, string $default_method = '' ) {

			if ( $this->isClosure( $callback ) ) {
				return new ReflectionFunction( $callback );
			}

			[ $class, $method ] = ( $this->classExists( $callback[0] ) )
				? [ $callback[0], $callback[1] ?? $default_method ]
				: Str::parseCallback( $callback[0], $default_method );

			return new ReflectionMethod( $class, $method );

		}


		/**
		 * @param string|array|Closure $callable
		 * @param $payload
		 *
		 * @return array
		 * @throws \ReflectionException
		 */
		private function buildNamedParameters( $callable , $payload ) : array {


			if ( is_string($callable)) {

				$callable = Str::parseCallback($callable);

			}

			$payload = ( ! is_array( $payload ) ) ? [ $payload ] : $payload;

			$call_reflector = $this->getCallReflector( $callable , 'handle' );

			$params = collect( $call_reflector->getParameters() );

			$parameter_names = $params->map( function ( $param ) {

				return $param->getName();

			} );

			if ( $parameter_names->isEmpty() ) {

				return $payload;

			}

			$reduced = $parameter_names->slice( 0, count( ( $payload ) ) );

			$payload = $reduced->combine( $payload );

			return $payload->all();


		}

		/**
		 * @param $class_name_or_object
		 *
		 * @return bool
		 */
		private function classExists ($class_name_or_object): bool {

			if ( is_object( $class_name_or_object) ) return TRUE;

			return class_exists($class_name_or_object);

		}

		/**
		 * Accepts a string that contains and @ and returns the part before the @.
		 *
		 *
		 * @param $object
		 *
		 * @return bool
		 */
		private function isClosure( $object ): bool {

			return $object instanceof Closure;

		}

		private function classImplements ( $class, $interface) : bool {

			$used_interfaces = class_implements($class);

			return isset($used_interfaces[$interface]);

		}




	}
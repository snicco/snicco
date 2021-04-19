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


		private function buildNameConstructorParameters ( $class, $payload  ) {

			$payload = collect(Arr::wrap($payload));

			if ( ! $this->classExists($class ) ) {

				return $payload;

			}

			$class = new ReflectionClass($class);
			$constructor = $class->getConstructor();

			$params = collect( $constructor->getParameters() );

			$parameter_names = $params->map( function ( $param ) {

				return $param->getName();

			} );

			if ( $parameter_names->isEmpty() ) {

				return $payload;

			}


			$parameter_types = $params->map( function ( $param ) {

				$type = $param->getType();

				$name = $type->getName();

				return $name;

			} );

			$payload = $payload->flatMap(function ($value) use ($parameter_names, $parameter_types) {

				if ( $this->parameterType($value) === $parameter_types->shift()) {

					return [$parameter_names->shift() => $value];

				}

				return $value;

			});


			return $payload->toArray();

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

			return $payload->toArray();


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

		private function parameterType($param) {

				$type = gettype($param);

				if ( $type != 'object') return $type;

				return get_class($param);

		}


	}
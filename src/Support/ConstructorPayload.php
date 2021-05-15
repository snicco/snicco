<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use Illuminate\Support\Collection;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionParameter;

    use function Patchwork\Utils\args;

    class ConstructorPayload
    {

        public static function byTypeHint(string $class, $args) : array
        {
            $args = Arr::wrap($args);
            $reflection_constructor = self::getConstructor( $class );

            if ( ! $reflection_constructor ) {
                return [];
            }

            $params = self::parameterCollection($reflection_constructor);
            $payload = self::payload($args);

            $constructor_names = $params->map(function (ReflectionParameter $param) {

                return $param->getName();

            });
            $constructor = self::namedConstructorWithTypes($params);

            $payload = $payload
                ->filter(function (array $type_and_value ) use ($constructor) {

                    return self::hasOneOfSameType($type_and_value, $constructor);

                });

            $as_flattened_array = $constructor->mapWithKeys(function ($a) {
                return $a;
            })->all();

            $payload = $payload
                ->flatMap(function (array $type_and_value) use (&$as_flattened_array) {

                    return self::buildNameAndValue(
                        $type_and_value,
                        $as_flattened_array
                    );

                });

            $payload = $payload->reject(function ($value, $param_name) use ($constructor_names) {

                    return $constructor_names->search($param_name) === false;

                });

            // Is not safe to to just return the optional params with values from the constructor
            // since values from $args could overwrite these.
            // The payload being empty most likely means that the developer has not provided typehints
            // so we just build the named payload by order and hope the dev provided correct types.
            if ( $payload->isEmpty() && $args !== [] ) {

                return self::byOrder($class, $args);

            }

            $optional = self::optionalParams($params);

            if( $args === [] ) {

                return $optional->all();

            }

            $arg_count = count($args);
            $payload_count = $payload->count();
            $diff = $arg_count - $payload_count;

            if ( $diff > 0 ) {

                $offset = $constructor_names->count() -1;
                $missing_payload = collect($args)->skip($arg_count - $diff);

                $missing_names = $constructor_names->slice($offset, $diff);

                // $missing_names = $constructor_names->diff($payload->keys());

                $missing_payload = $missing_payload->slice(0, $missing_names->count());

                $append_payload = $missing_names->values()
                                                ->combine($missing_payload);

                foreach ($append_payload as $name => $value )  {

                    if ( ! self::isOptional($name, $reflection_constructor) ) {

                        $payload->put( $name , $value );

                    }

                }


            }


            return $payload->union($optional)->all();


        }

        public static function byOrder(string $class, array $arguments) : array
        {

            $payload = Arr::wrap($arguments);

            $constructor = self::getConstructor($class);

            if ( ! $constructor ) {

                return $arguments;

            }

            $params = self::parameterCollection($constructor);

            $parameter_names = $params->map( function ( $param ) {

                return $param->getName();

            });

            if ( $parameter_names->isEmpty() ) {

                return $payload;

            }

            $reduced = $parameter_names->slice( 0, count( ( $payload ) ) );

            $payload = $reduced->combine( $payload );

            return $payload->all();

        }

        private static function buildNameAndValue(array $payload_type_and_value, array &$constructor_name_and_type) {

            $type = Arr::firstKey($payload_type_and_value);
            $value = Arr::firstEl($payload_type_and_value);
            $name = Arr::pullByValueReturnKey($type, $constructor_name_and_type);
            return [$name => $value];

        }

        /**
         * @throws \ReflectionException
         */
        private static function getConstructor(string $class) : ?ReflectionMethod
        {

            return (new ReflectionClass($class))->getConstructor();
        }

        private static function parameterCollection(ReflectionMethod $constructor) : Collection
        {

            return collect($constructor->getParameters());

        }

        private static function typeHints(Collection $params) : Collection
        {

            return $params
                ->map(function (ReflectionParameter $param) {

                    $type = $param->getType();

                    $type =  ($type) ? $type->getName() : $param->getName();

                    return ($type === 'bool') ? 'boolean' : $type;

                });


        }

        private static function payload($args) :Collection
        {

            $payload = collect($args);
            $payload_values = $payload->values()->all();
            $payload_types = $payload->map(function ($value) {

                return is_object($value) ? get_class($value) : gettype($value);

            });
            $payload = $payload_types->map(function ($type) use (&$payload_values) {

                return [$type => array_shift($payload_values)];

            });

            return $payload;

        }

        private static function hasOneOfSameType ( array $type_and_value, Collection $constructor ) : bool
        {
            $include = false;

            $constructor->each(function ( $param_name_and_type ) use ( $type_and_value, &$include){

                $constructor_type = Arr::firstEl($param_name_and_type);
                $payload_type = Arr::firstKey($type_and_value);

                $matching_types = $constructor_type === $payload_type;

                if ( $matching_types === true ) {

                    $include = true;

                    // stop iteration.
                    return false;

                }


            });

            return $include;

        }

        private static function optionalParams (Collection $reflection_params) :Collection {

            $optional = $reflection_params->filter(function (ReflectionParameter $param) {

                return $param->isOptional() && ! $param->isVariadic();

            });

            if ($optional->isEmpty()) {

               return $optional;

            }

            return $optional->flatMap(function ( ReflectionParameter $param ) {

                return [$param->getName() => $param->getDefaultValue()];

            });


        }

        /**
         * @param  Collection  $params [name => 'type']
         *
         * @return Collection
         */
        private static function namedConstructorWithTypes(Collection $params) :Collection
        {
            $constructor_names = $params->map(function (ReflectionParameter $param) {

                return $param->getName();

            });
            $types_as_array = self::typeHints($params)->values()->all();
            $constructor = $constructor_names->map(function ( $name ) use ( &$types_as_array ) {

                return [$name => array_shift($types_as_array)];

            });

            return $constructor;
        }

        private static function isOptional($name, ReflectionMethod $constructor)
        {

            $optional = self::optionalParams(collect($constructor->getParameters()));

            return $optional->has($name);



        }


    }
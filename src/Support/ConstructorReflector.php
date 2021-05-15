<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use Illuminate\Support\Collection;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionParameter;

    class ConstructorReflector
    {

        public static function namedConstructorArgs(string $class, $args)
        {

            $params = self::parameterCollection(self::getConstructor($class));
            $payload = self::payload($args);

            $constructor_names = $params->map(function (ReflectionParameter $param) {

                return $param->getName();

            });
            $constructor_types = self::typeHints($params);
            $constructor = $constructor_types->combine($constructor_names);

            $payload = $payload
                ->filter(function (array $type_and_value ) use ($constructor) {

                    return self::includeParameter($type_and_value, $constructor);

                });

            $payload = $payload
                ->flatMap(function (array $type_and_value) use ($constructor) {

                    return [$constructor->pull(Arr::firstKey($type_and_value)) => Arr::first($type_and_value)];

                });

            $payload = $payload->reject(function ($value, $param_name) use ($constructor_names) {

                return $constructor_names->search($param_name) === false;

            });

            $optional = self::optionalParams($params);

            return $payload->merge($optional)->all();


        }

        /**
         * @throws \ReflectionException
         */
        private static function getConstructor(string $class)
        {

            return (new ReflectionClass($class))->getConstructor();
        }

        private static function parameterCollection(ReflectionMethod $constructor)
        {

            return collect($constructor->getParameters());

        }

        private static function typeHints(Collection $params) : Collection
        {

            return $params
                ->map(function (ReflectionParameter $param) {

                    $type = $param->getType();

                    return ($type) ? $type->getName() : $param->getName();
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

        private static function includeParameter (array $type_and_value, Collection $constructor) : bool
        {

            return $constructor->has(Arr::firstKey($type_and_value));

        }

        private static function optionalParams (Collection $reflection_params) {

            $optional = $reflection_params->filter(function (ReflectionParameter $param) {

                return $param->isOptional() && ! $param->isVariadic();

            });

            if ($optional->isEmpty()) {

               return $optional;

            }

            $optional = $optional->flatMap(function ( ReflectionParameter $param ) {

                return [$param->getName() => $param->getDefaultValue()];

            });

            return $optional;


        }

    }
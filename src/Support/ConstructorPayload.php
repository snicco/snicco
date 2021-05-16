<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use Illuminate\Support\Collection;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionParameter;

    class ConstructorPayload
    {

        /** @var array */
        private $original_payload;

        /** @var string */
        private $class;

        /**
         * @var ReflectionMethod|null
         */
        private $reflection_constructor;

        /**
         * @var Collection
         */
        private $reflection_params;

        /**
         * @var Collection
         */
        private $constructor_names;

        /**
         * @var Collection
         */
        private $constructor_types_by_name;

        /**
         * @var Collection
         */

        private $payload_by_type_and_value;

        /**
         * @var Collection
         */
        private $optional_params_with_defaults;


        public function __construct($class, $original_payload)
        {

            $this->class = $class;
            $this->original_payload = Arr::wrap($original_payload);
            $this->payload_by_type_and_value = $this->parsePayload();
            $this->reflection_constructor = $this->parseReflectionConstructor();

            if ($this->reflection_constructor) {

                $this->reflection_params = collect($this->reflection_constructor->getParameters());
                $this->constructor_names = $this->parseNamedConstructorArgs();
                $this->constructor_types_by_name = $this->parseConstructorTypesByName();
                $this->optional_params_with_defaults = $this->optionalParams();

            }

        }

        public function build() : array
        {

            if ( ! $this->reflection_constructor) {
                return [];
            }

            if ( $this->isFirstVariadic () ) {

                return $this->createVariadicOneParam();

            }


            if ( $pos = $this->hasVariadic() ) {

              return $this->createVariadicPayload($pos);

            }


            $payload = $this->payload_by_type_and_value
                ->filter(function (array $type_and_value) {

                    return $this->constructorHasOneOfSameType($type_and_value);

                });

            $as_array = $this->constructor_types_by_name->all();

            $payload = $payload
                ->flatMap(function (array $type_and_value) use (&$as_array) {

                    return $this->buildNameAndValue(
                        $type_and_value,
                        $as_array
                    );

                });

            $payload = $payload->reject(function ($value, $param_name) {

                return ! $this->constructorHasParamWithSameName($param_name);

            });





            // Is not safe to to just return the optional params with values from the constructor
            // since values from the original payload could overwrite these.
            // The payload being empty most likely means that the developer has not provided typehints
            // so we just build the named payload by order and hope the dev provided correct types.
            if ($payload->isEmpty() && $this->original_payload !== []) {

                return $this->byOrder();

            }

            if ($this->original_payload === []) {

                return $this->optional_params_with_defaults->all();

            }

            $diff = count($this->original_payload) - $payload->count();

            if ($constructor_args_without_value = $diff > 0) {

                $this->mergeMissingArguments($payload);


            }

            return $payload->union($this->optional_params_with_defaults)->all();

        }

        public function byOrder(array $payload = null ) : array
        {

            $payload = collect($payload ?? $this->original_payload);

            if ( ! ($this->reflection_constructor)) {

                return $payload->all();

            }

            $parameter_names = $this->constructor_names;

            // Lets hope for the best, because no typehints are provided.
            // Nothing we can do here.
            if ($parameter_names->isEmpty()) {

                return $payload->all();

            }

            if ($parameter_names->count() > $count = $payload->count()) {

                $parameter_names = $parameter_names->slice(0, $count);

            }

            if ($count = $parameter_names->count() < $payload->count()) {

                $payload = $payload->slice(0, $count);

            }

            $payload = $parameter_names->combine($payload);

            return $payload->all();

        }

        /**
         * @return Collection items: ['name' => 'type']
         */
        private function parseConstructorTypesByName() : Collection
        {

            $types_as_array = $this->typeHints()->values()->all();

            return $this->constructor_names->flatMap(function ($name) use (&$types_as_array) {

                return [$name => array_shift($types_as_array)];

            });

        }

        /**
         * @return Collection items: ['type' => 'value']
         */
        private function parsePayload() : Collection
        {

            $payload = collect($this->original_payload);
            $payload_values = $payload->values()->all();
            $payload_types = $payload->map(function ($value) {

                return is_object($value) ? get_class($value) : gettype($value);

            });

            return $payload_types->map(function ($type) use (&$payload_values) {

                return [$type => array_shift($payload_values)];

            });

        }

        private function parseReflectionConstructor() : ?ReflectionMethod
        {

            $reflection_class = (new ReflectionClass($this->class));

            if ( ! $reflection_class) {

                return null;

            }

            return $reflection_class->getConstructor();

        }

        private function parseNamedConstructorArgs() : Collection
        {

            return $this->reflection_params->map(function (ReflectionParameter $param) {

                return $param->getName();

            });


        }

        private function buildNameAndValue(array $payload_type_and_value, array &$constructor_name_and_type) : array
        {

            $payload_type = Arr::firstKey($payload_type_and_value);
            $payload_value = Arr::firstEl($payload_type_and_value);
            $constructor_param_name = Arr::pullByValueReturnKey($payload_type, $constructor_name_and_type);

            return [$constructor_param_name => $payload_value];

        }

        private function typeHints() : Collection
        {

            return $this->reflection_params
                ->map(function (ReflectionParameter $param) {

                    $type = $param->getType();

                    $type = ($type) ? $type->getName() : $param->getName();

                    return ($type === 'bool') ? 'boolean' : $type;

                });


        }

        private function constructorHasOneOfSameType(array $type_and_value) : bool
        {

            $include = false;

            $this->constructor_types_by_name->each(function ($constructor_type) use ($type_and_value, &$include) {

                $payload_type = Arr::firstKey($type_and_value);

                $matching_types = $constructor_type === $payload_type;

                if ($matching_types === true) {

                    $include = true;

                    // stop iteration.
                    return false;

                }


            });

            return $include;

        }

        private function optionalParams() : Collection
        {

            $optional = $this->reflection_params->filter(function (ReflectionParameter $param) {

                return $param->isOptional() && ! $param->isVariadic();

            });

            if ($optional->isEmpty()) {

                return $optional;

            }

            return $optional->flatMap(function (ReflectionParameter $param) {

                return [$param->getName() => $param->getDefaultValue()];

            });


        }

        private function isOptional($name) : bool
        {

            return $this->optional_params_with_defaults->has($name);


        }

        private function constructorHasParamWithSameName($param_name) : bool
        {

            return $this->constructor_names->search($param_name) !== false;
        }

        private function mergeMissingArguments(Collection $build_payload) : void
        {

            $unused_payload_values = $this->unUsedPayloadValues($build_payload)->all();

            $constructor_params_without_values = $this->paramsWithoutValue($build_payload);

            foreach ($constructor_params_without_values as $name) {

                if ( ! $this->isOptional($name) && $this->wantsPrimitive($name)) {

                    $build_payload->put($name, array_shift($unused_payload_values));

                }

            }
        }

        private function unUsedPayloadValues(Collection $built_payload) : Collection
        {

            $unused_values = collect($this->original_payload)->diffAssoc($built_payload->values());

            return $unused_values->values();

        }

        private function paramsWithoutValue(Collection $built_payload) : Collection
        {

            $keys = $this->constructor_types_by_name->keys();
            $payload_keys = $built_payload->keys();

            $unused = $keys->diff($payload_keys);

            return $unused->values();

        }

        private function wantsPrimitive($name) : bool
        {

            $type = $this->constructor_types_by_name->get($name);

            return ! class_exists($type);


        }

        private function isFirstVariadic() :bool
        {

            $first = $this->reflection_params->first();

            return $first->isVariadic();


        }

        private function hasVariadic() : ?int
        {

            /** @var ReflectionParameter $variadic */
            $variadic = $this->reflection_params->first(function (ReflectionParameter $parameter)  {

                return $parameter->isVariadic();

            });

            if ( ! $variadic ) {

                return null;

            }

            return $variadic->getPosition();

        }

        private function createVariadicPayload( int $variadic_param_position ) :array
        {

            /** @var ReflectionParameter $variadic_param */
            $variadic_param = $this->reflection_params->get($variadic_param_position);

            $payload = collect($this->original_payload);

            $ordered =  collect($this->byOrder( $payload->slice(0, $variadic_param_position)->all() ));

            $variadic = collect($payload)->diffAssoc($ordered->values());

            $variadic = [$variadic_param->getName() => $variadic->values()->all()];

            $final = $ordered->union($variadic);

            return $final->all();

        }

        private function createVariadicOneParam() : array
        {

            return $this->original_payload;


        }


    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use Closure;
    use Illuminate\Support\Collection;
    use ReflectionClass;
    use ReflectionFunctionAbstract;
    use ReflectionMethod;
    use ReflectionParameter;

    class ReflectionPayload
    {

        /** @var array */
        private $original_payload;

        /** @var mixed */
        private $target;

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

        /**
         * ReflectionPayload constructor.
         *
         * @param  string|array|Closure  $target  either a FQN class as string, or a class
         *     callable/closure
         *
         * @param  mixed  $original_payload  The runtime arguments available to construct a named
         *     payload
         */
        public function __construct($target, $original_payload)
        {

            $this->target = $target;
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

            if ( ! $this->reflection_constructor || $this->reflection_constructor->getParameters() === [] ) {
                return [];
            }

            if ($this->isFirstVariadic()) {

                return $this->createVariadicOneParam();

            }

            if ($pos = $this->hasVariadic()) {

                return $this->createVariadicPayload($pos);

            }

            if ($this->everyParamHasTypeHint()) {

                $payload = $this->createTypeHinted();

                return collect($payload)->union($this->optional_params_with_defaults)->all();

            }

            $copy = $this->reflection_params->all();
            $include = collect([]);

            while (($param = Arr::firstEl($copy))->hasType()) {

                $include->add($param->getName());
                array_shift($copy);

            }

            if ( $include->isNotEmpty() ) {

                $only = $this->constructor_types_by_name->only($include->values());

                $type_hinted = $this->createTypeHinted($only->all());

                $remaining_payload = collect($this->original_payload)->skip(count($type_hinted));

                $except = $this->constructor_types_by_name->except($include->values());

                $by_order = $this->byOrder($remaining_payload->all(), $except->keys());

                $payload = array_merge($type_hinted, $by_order);

                return collect($payload)->union($this->optional_params_with_defaults)->all();

            }

            return $this->byOrder();


        }

        private function createTypeHinted($constructor_array = null) : array
        {

            $payload = $this->payload_by_type_and_value
                ->filter(function (array $type_and_value) {

                    return $this->constructorHasOneOfSameType($type_and_value);

                });

            $as_array = $constructor_array ?? $this->constructor_types_by_name->all();

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

            return $payload->all();


        }

        public function byOrder(array $payload = null, $param_names = null) : array
        {

            $payload = collect($payload ?? $this->original_payload);

            if ( ! ($this->reflection_constructor)) {

                return $payload->all();

            }

            $parameter_names = $param_names ?? $this->constructor_names;

            // Lets hope for the best, because no typehints are provided.
            // Nothing we can do here.
            if ($parameter_names->isEmpty()) {

                return $payload->all();

            }

            if ($parameter_names->count() > $count = $payload->count()) {

                $parameter_names = $parameter_names->slice(0, $count);

            }

            if ( ($count = $parameter_names->count()) < $payload->count()) {

                $payload = $payload->slice(0, $count);

            }

            $new = $parameter_names->combine($payload);

            return $new->all();

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

        private function parseReflectionConstructor() : ?ReflectionFunctionAbstract
        {

            $reflection_method = null;

            if (is_string($this->target)) {

                $reflection_method = (new ReflectionClass($this->target))->getConstructor();

            }

            if (is_array($this->target)) {

                return new ReflectionMethod(...$this->target);

            }

            if ($this->target instanceof Closure) {

                return new \ReflectionFunction($this->target);

            }

            return $reflection_method;

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

        private function constructorHasParamWithSameName($param_name) : bool
        {

            return $this->constructor_names->search($param_name) !== false;
        }

        private function isFirstVariadic() : bool
        {

            $first = $this->reflection_params->first();

            return $first->isVariadic();


        }

        private function hasVariadic() : ?int
        {

            /** @var ReflectionParameter $variadic */
            $variadic = $this->reflection_params->first(function (ReflectionParameter $parameter) {

                return $parameter->isVariadic();

            });

            if ( ! $variadic) {

                return null;

            }

            return $variadic->getPosition();

        }

        private function createVariadicPayload(int $variadic_param_position) : array
        {

            /** @var ReflectionParameter $variadic_param */
            $variadic_param = $this->reflection_params->get($variadic_param_position);

            $payload = collect($this->original_payload);

            $ordered = collect($this->byOrder($payload->slice(0, $variadic_param_position)->all()));

            $variadic = collect($payload)->diffAssoc($ordered->values());

            $variadic = [$variadic_param->getName() => $variadic->values()->all()];

            $final = $ordered->union($variadic);

            return $final->all();

        }

        private function createVariadicOneParam() : array
        {

            return $this->original_payload;


        }

        private function everyParamHasTypeHint() : bool
        {

            $withoutType = $this->reflection_params->first(function (ReflectionParameter $param) {

                return ! $param->hasType();

            });

            return $withoutType === null;

        }


    }
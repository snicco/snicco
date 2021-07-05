<?php


    declare(strict_types = 1);


    namespace WPMvc\Factories;

    use Contracts\ContainerAdapter;
    use WPMvc\Contracts\ConditionInterface;
    use WPMvc\ExceptionHandling\Exceptions\ConfigurationException;
    use WPMvc\Routing\ConditionBlueprint;
    use WPMvc\Routing\Conditions\CustomCondition;
    use WPMvc\Routing\Conditions\NegateCondition;
    use WPMvc\Routing\Route;
    use WPMvc\Traits\ReflectsCallable;

    class ConditionFactory
    {

        use ReflectsCallable;

        /**
         * Registered condition types.
         *
         * @var array<string, string>
         */
        private $condition_types;

        /**
         * @var ContainerAdapter
         */
        private $container;


        public function __construct(array $condition_types, ContainerAdapter $container)
        {

            $this->condition_types = $condition_types;
            $this->container = $container;
        }

        public function buildConditions( array $raw_conditions ) : array
        {

            $conditions = collect($raw_conditions);

            $conditions = $conditions
                ->map(function (ConditionBlueprint $condition) {

                    if ($compiled = $this->alreadyCompiled($condition)) {

                        return $compiled;

                    }

                    return $this->new($condition);


                })
                ->unique();

            return $conditions->all();

        }

        private function new(ConditionBlueprint $blueprint)
        {

            try {

                $type = $this->transformAliasToClassName($blueprint->type());
                $conditions_arguments = $blueprint->args();

                if ($type === $this->condition_types[ConditionBlueprint::NEGATES_WORD]) {

                    return $this->newNegated($blueprint, $conditions_arguments);

                }

                $args = $this->buildNamedConstructorArgs(
                    $type,
                    $conditions_arguments
                );

                return $blueprint->instance() ?? $this->container->make($type, $args);
            }
            catch (\Throwable $e) {

                throw new ConfigurationException(
                    "Condition could not be created.".PHP_EOL.$e->getMessage()

                );
            }


        }

        private function transformAliasToClassName(string $type)
        {

            return $this->condition_types[$type] ?? $type;

        }

        private function alreadyCompiled(ConditionBlueprint $condition) : ?object
        {

            if ($condition->type() === ConditionBlueprint::NEGATES_WORD) {

                return null;

            }

            return $condition->instance();

        }

        private function newNegated(ConditionBlueprint $blueprint, array $args) : NegateCondition
        {

            $instance = $blueprint->instance();

            if ($instance instanceof ConditionInterface) {

                return new NegateCondition($instance);

            }

            if (is_callable($instance)) {

                return new NegateCondition(new CustomCondition($instance, ...$args));

            }

            if (is_callable($negates = $blueprint->negates())) {


                return new NegateCondition(new CustomCondition($negates, ...$args));

            }

            $instance = $this->container->make(

                $type = $this->transformAliasToClassName($negates),
                $this->buildNamedConstructorArgs($type, $args)

            );

            return new NegateCondition($instance);


        }


    }

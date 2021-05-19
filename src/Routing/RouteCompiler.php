<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Illuminate\Support\Str;
    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\HandlerFactory;

    class RouteCompiler
    {

        /**
         * @var HandlerFactory
         */
        private $handler_factory;
        /**
         * @var ConditionFactory
         */
        private $condition_factory;

        public function __construct(HandlerFactory $handler_factory, ConditionFactory $condition_factory)
        {

            $this->handler_factory = $handler_factory;
            $this->condition_factory = $condition_factory;
        }

        public function hydrate(array $attributes) : CompiledRoute
        {

            $compiled = new CompiledRoute($attributes);

            if ($this->isSerializedClosure($action = $compiled->action)) {

                $action = \Opis\Closure\unserialize($action);

            }

            $compiled->action = $this->compileRouteAction($compiled, $action);
            $compiled->conditions = $this->compileConditions($compiled);

            return $compiled;

        }

        public function buildConditions( Route $route ) {

            $this->condition_factory->compileConditions($route);

        }

        private function isSerializedClosure($action) : bool
        {

            return is_string($action)
                && Str::startsWith($action, 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
        }

        private function compileRouteAction(CompiledRoute $compiled, $action)
        {

            return $this->handler_factory->create($action, $compiled->namespace);

        }

        private function compileConditions(CompiledRoute $compiled) : array
        {

            return $this->condition_factory->compileConditions($compiled);
        }

        public function compileUrlableConditions(array $compiled) : CompiledRoute
        {

            $compiled = new CompiledRoute($compiled);

            $compiled->conditions = $this->compileConditions($compiled);

            return $compiled;

        }




    }
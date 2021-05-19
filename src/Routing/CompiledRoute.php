<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Illuminate\Support\Str;
    use Opis\Closure\SerializableClosure;
    use WPEmerge\Contracts\RouteAction;
    use WPEmerge\Contracts\RouteCondition;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Support\ReflectionPayload;

    class CompiledRoute implements RouteCondition
    {

        /** @var RouteAction|string */
        public $action;

        public $middleware;

        public $conditions;

        public $namespace;

        /**
         * @var array
         */
        public $defaults;

        public $url;

        public $wp_query_filter;

        public function __construct($attributes)
        {

            $this->action = $attributes['action'];
            $this->middleware = $attributes['middleware'] ?? [];
            $this->conditions = $attributes['conditions'] ?? [];
            $this->namespace = $attributes['namespace'] ?? '';
            $this->defaults = $attributes['defaults'] ?? [];
            $this->url = $attributes['url'] ?? '';
            $this->wp_query_filter = $attributes['wp_query_filter'];

        }

        public function satisfiedBy(Request $request) : bool
        {

            $failed_condition = collect($this->conditions)
                ->first(function ($condition) use ($request) {

                    return ! $condition->isSatisfied($request);

                });

            return $failed_condition === null;

        }

        public static function hydrate(
            array $attributes,
            HandlerFactory $handler_factory,
            ConditionFactory $condition_factory
        ) : CompiledRoute {

            $compiled = new static($attributes);

            if ($compiled->isSerializedClosure($action = $compiled->action)) {

                $action = \Opis\Closure\unserialize($action);

            }

            $compiled->action = $handler_factory->create($action, $compiled->namespace);
            $compiled->conditions = $condition_factory->compileConditions($compiled);

            return $compiled;

        }

        public function middleware() : array
        {

            return array_merge(
                $this->middleware,
                $this->controllerMiddleware()

            );

        }

        private function controllerMiddleware() : array
        {

            if ( ! $this->usesController()) {

                return [];
            }

            return $this->action->resolveControllerMiddleware();

        }

        private function usesController() : bool
        {

            return ! $this->action->raw() instanceof Closure;

        }

        public function run(Request $request, array $payload)
        {

            $payload = array_merge([$request], $payload);

            $reflection_payload = new ReflectionPayload($this->action->raw(), array_values($payload));

            return $this->action->executeUsing(
                $this->mergeDefaults($reflection_payload->build())
            );


        }

        public function getConditions()
        {

            return $this->conditions;

        }

        public function compileCacheableAction() : CompiledRoute
        {

            if ($this->action instanceof Closure && class_exists(SerializableClosure::class)) {

                $closure = new SerializableClosure($this->action);

                $this->action = \Opis\Closure\serialize($closure);

            }

            return $this;

        }

        private function isSerializedClosure($action) : bool
        {

            return is_string($action)
                && Str::startsWith($action, 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
        }

        private function mergeDefaults(array $route_payload) : array
        {

            return array_merge($route_payload, $this->defaults);


        }

        public function filterWpQuery ( array $query_vars,  array $route_payload ) {

            $callable = $this->wp_query_filter;

            if ( ! $callable ) {

                return $query_vars;

            }

            $combined = [$query_vars] + $route_payload;

            return call_user_func_array($callable, $combined);

        }

    }
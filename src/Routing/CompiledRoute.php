<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Opis\Closure\SerializableClosure;
    use WPEmerge\Contracts\RouteAction;
    use WPEmerge\Contracts\RouteCondition;
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

        public $regex;

        /**
         * @var array
         */
        public $segments;

        /**
         * @var array
         */
        public $segment_names;

        /**
         * @var bool
         */
        public $trailing_slash;

        public $name;

        /**
         * @var array
         */
        public $methods;

        public function __construct($attributes)
        {

            $this->action = $this->compileCacheableAction($attributes['action'] ?? '');
            $this->middleware = $attributes['middleware'] ?? [];
            $this->conditions = $attributes['conditions'] ?? [];
            $this->namespace = $attributes['namespace'] ?? '';
            $this->defaults = $attributes['defaults'] ?? [];
            $this->url = $attributes['url'] ?? '';
            $this->wp_query_filter = $attributes['wp_query_filter'];
            $this->regex = $attributes['regex'] ?? [];
            $this->segments = $attributes['segments'] ?? [];
            $this->segment_names = $attributes['segment_names'] ?? [];
            $this->trailing_slash = $attributes['trailing_slash'] ?? false;
            $this->name = $attributes['name'] ?? '';
            $this->methods = $attributes['methods'] ?? [];

        }

        public function satisfiedBy(Request $request) : bool
        {

            $failed_condition = collect($this->conditions)
                ->first(function ($condition) use ($request) {

                    return ! $condition->isSatisfied($request);

                });

            return $failed_condition === null;

        }

        public function middleware() : array
        {

            return array_merge(
                $this->middleware,
                $this->controllerMiddleware()

            );

        }

        public function filterWpQuery ( array $query_vars,  array $route_payload ) {

            $callable = $this->wp_query_filter;

            if ( ! $callable ) {

                return $query_vars;

            }

            $combined = [$query_vars] + $route_payload;

            return call_user_func_array($callable, $combined);

        }

        public function run(Request $request, array $payload)
        {

            $payload = array_merge([$request], $payload);

            $reflection_payload = new ReflectionPayload($this->action->raw(), array_values($payload));

            return $this->action->executeUsing(
                $this->mergeDefaults($reflection_payload->build())
            );


        }

        public function toArray() : array
        {
            return (array) $this;
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

        public function getConditions()
        {

            return $this->conditions;

        }

        /**
         * @param $action
         *
         * @return RouteAction|string|Closure
         */
        private function compileCacheableAction($action)
        {

            if ($action instanceof Closure && class_exists(SerializableClosure::class)) {

                $closure = new SerializableClosure($action);

                $action = \Opis\Closure\serialize($closure);

            }

            return $action;

        }

        private function mergeDefaults(array $route_payload) : array
        {

            return array_merge($route_payload, $this->defaults);


        }


    }
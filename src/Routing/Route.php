<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Opis\Closure\SerializableClosure;
    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\RouteAction;
    use WPEmerge\Contracts\RouteCondition;
    use WPEmerge\Contracts\SetsRouteAttributes;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Conditions\TrailingSlashCondition;
    use WPEmerge\Support\ReflectionPayload;
    use WPEmerge\Support\Url;
    use WPEmerge\Support\UrlParser;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;
    use WPEmerge\Traits\SetRouteAttributes;

    class Route implements RouteCondition, SetsRouteAttributes
    {

        use SetRouteAttributes;

        const ROUTE_WILDCARD = '*';

        /**
         * @var array
         */
        private $methods;

        /**
         * @var string
         */
        private $url;

        /** @var string|Closure|array */
        private $action;

        /** @var ConditionBlueprint[] */
        private $conditions = [];

        /**
         * @var array
         */
        private $middleware;

        /** @var string */
        private $namespace;

        /** @var string */
        private $name;

        /**
         * @var ConditionInterface[]
         */
        private $compiled_conditions = [];

        /** @var array */
        private $regex = [];

        /** @var array */
        private $defaults = [];

        /**
         * @var Closure|null
         */
        private $wp_query_filter = null;

        /** @var array */
        private $segment_names = [];

        /**
         * @var array
         */
        private $segments = [];

        /**
         * @var bool
         */

        private $trailing_slash = false;

        /**
         * @var RouteAction
         */
        private $compiled_action;

        public function __construct(array $methods, string $url, $action, array $attributes = [])
        {

            $this->methods = $methods;
            $this->url = $this->parseUrl($url);
            $this->action = $action;
            $this->namespace = $attributes['namespace'] ?? '';
            $this->middleware = $attributes['middleware'] ?? [];


        }

        private function parseUrl(string $url) : string
        {

            $url = UrlParser::replaceAdminAliases($url);

            $url = Url::addLeading($url);

            $this->segments = UrlParser::segments($url);

            $this->segment_names = UrlParser::segmentNames($url);

            return $url;

        }

        public static function hydrate(array $attributes) : Route
        {
            $route = new Route(
                $attributes['methods'],
                $attributes['url'],
                $action = $attributes['action']
            );

            $route->action = $route->unserializeAction($action);
            $route->middleware = $attributes['middleware'] ?? [];
            $route ->conditions = $attributes['conditions'] ?? [];
            $route ->namespace = $attributes['namespace'] ?? '';
            $route ->defaults = $attributes['defaults'] ?? [];
            $route ->url = $attributes['url'] ?? '';
            $route ->wp_query_filter = $attributes['wp_query_filter'];
            $route ->regex = $attributes['regex'] ?? [];
            $route ->segments = $attributes['segments'] ?? [];
            $route ->segment_names = $attributes['segment_names'] ?? [];
            $route ->trailing_slash = $attributes['trailing_slash'] ?? false;
            $route ->name = $attributes['name'] ?? '';
            $route ->methods = $attributes['methods'] ?? [];

            return $route;

        }

        public function asArray () :array {

            return [
                'action' => $this->serializeAction($this->action),
                'name' => $this->name,
                'middleware' => $this->middleware ?? [],
                'conditions' => $this->conditions ?? [],
                'namespace' => $this->namespace ?? '',
                'defaults' => $this->defaults ?? [],
                'url' => $this->url,
                'wp_query_filter' => $this->wp_query_filter,
                'regex' => $this->regex,
                'segments' => $this->segments,
                'segment_names' => $this->segment_names,
                'trailing_slash' => $this->trailing_slash,
                'methods' => $this->methods,
            ];

        }

        public function and(...$regex) : Route
        {

            $regex_array = $this->normalizeRegex($regex);


            /** @todo This needs to added instead of replaced regex */
            $this->regex[] = $regex_array;

            return $this;

        }

        public function andAlpha() : Route
        {

            return $this->addRegexToSegment(func_get_args(), '[a-zA-Z]+');

        }

        public function andNumber() : Route
        {

            return $this->addRegexToSegment(func_get_args(), '[0-9]+');

        }

        public function andAlphaNumerical() : Route
        {
            return $this->addRegexToSegment(func_get_args(), '[a-zA-Z0-9]+');

        }

        public function andEither(string $segment, array $pool) : Route
        {

            return $this->addRegexToSegment($segment, implode('|', $pool));

        }

        public function getMethods() : array
        {

            return $this->methods;

        }

        public function getRegex() : array
        {

            return $this->regex;

        }

        public function getName() : ?string
        {

            return $this->name;

        }

        public function getConditions() : ?array
        {

            return $this->conditions;

        }

        public function getUrl() : string
        {

            return $this->url;

        }

        public function getCompiledConditions() : array
        {

            return $this->compiled_conditions;
        }

        public function compileConditions(ConditionFactory $condition_factory) : Route
        {

            $this->compiled_conditions = $condition_factory->compileConditions($this);

            return $this;

        }

        private function normalizeRegex($regex) : array
        {

            $regex = Arr::flattenOnePreserveKeys($regex);

            if (is_int(Arr::firstEl(array_keys($regex)))) {

                return Arr::combineFirstTwo($regex);

            }

            return $regex;

        }

        private function addRegexToSegment($segments, string $pattern) : Route
        {

            collect($segments)
                ->flatten()
                ->each(function ($segment) use ($pattern) {

                    $this->and($segment, $pattern);

                });

            return $this;

        }

        public function wpquery(Closure $callback) : Route
        {

            $this->wp_query_filter = $callback;

            return $this;

        }

        public function andOnlyTrailing() : Route
        {

            $this->where(TrailingSlashCondition::class);

            $this->trailing_slash = true;

            return $this;

        }

        public function needsTrailingSlash() : bool
        {
            return $this->trailing_slash;
        }

        public function segmentNames() :array
        {
            return $this->segment_names;
        }

        public function satisfiedBy(Request $request) : bool
        {

            $failed_condition = collect($this->compiled_conditions)
                ->first(function ($condition) use ($request) {

                    return ! $condition->isSatisfied($request);

                });

            return $failed_condition === null;

        }

        public function getMiddleware() : array
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

            return $this->compiled_action->resolveControllerMiddleware();

        }

        private function usesController() : bool
        {

            return ! $this->compiled_action->raw() instanceof Closure;

        }

        public function run(Request $request, array $payload)
        {

            $payload = array_merge([$request], $payload);

            $reflection_payload = new ReflectionPayload($this->compiled_action->raw(), array_values($payload));

            return $this->compiled_action->executeUsing(
                $this->mergeDefaults($reflection_payload->build())
            );


        }

        private function mergeDefaults(array $route_payload) : array
        {

            return array_merge($route_payload, $this->defaults);


        }

        public function compileAction(HandlerFactory $handler_factory)
        {
            $this->compiled_action = $handler_factory->create($this->action, $this->namespace);

        }

        private function serializeAction($action)
        {

            if ($action instanceof Closure && class_exists(SerializableClosure::class)) {

                $closure = new SerializableClosure($action);

                $action = \Opis\Closure\serialize($closure);

            }

            return $action;

        }

        private function unserializeAction($action) {

            if ($this->isSerializedClosure($action)) {

                $action = \Opis\Closure\unserialize($action);

            }

            return $action;

        }

        private function isSerializedClosure($action) : bool
        {

            return is_string($action)
                && Str::startsWith($action, 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
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
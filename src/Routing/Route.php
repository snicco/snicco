<?php


    declare(strict_types = 1);


    namespace Snicco\Routing;

    use Closure;
    use ReflectionPayload\ReflectionPayload;
    use Snicco\Contracts\ConditionInterface;
    use Snicco\Contracts\RouteAction;
    use Snicco\Contracts\SetsRouteAttributes;
    use Snicco\Contracts\UrlableInterface;
    use Snicco\Controllers\FallBackController;
    use Snicco\Factories\ConditionFactory;
    use Snicco\Factories\RouteActionFactory;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Url;
    use Snicco\Support\UrlParser;
    use Snicco\Traits\SetRouteAttributes;

    class Route implements SetsRouteAttributes
    {

        use SetRouteAttributes;

        const ROUTE_WILDCARD = '*';

        private array  $methods;
        private string $url;

        /** @var string|Closure|array */
        private $action;

        /** @var ConditionBlueprint[] */
        private array $condition_blueprints = [];

        private array   $middleware;
        private string  $namespace;
        private ?string $name;
        private array   $regex    = [];
        private array   $defaults = [];

        /**
         * @var Closure|string|null
         */
        private $wp_query_filter = null;

        private array $segment_names = [];

        private array $segments       = [];
        private bool  $trailing_slash = false;

        /**
         * @var ConditionInterface[]
         */
        private array $instantiated_conditions = [];

        private RouteAction         $instantiated_action;
        private ?RouteActionFactory $action_factory    = null;
        private ?ConditionFactory   $condition_factory = null;

        public function __construct(array $methods, string $url, $action, array $attributes = [])
        {

            $this->methods = $methods;
            $this->url = $this->parseUrl($url);
            $this->action = $action;
            $this->namespace = $attributes['namespace'] ?? '';
            $this->middleware = $attributes['middleware'] ?? [];


        }

        public static function hydrate(array $attributes) : Route
        {
            $route = new Route(
                $attributes['methods'],
                $attributes['url'],
                $attributes['action']
            );

            $route->middleware = $attributes['middleware'] ?? [];
            $route ->condition_blueprints = $route->hydrateConditionBlueprints($attributes['conditions'] ?? []);
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
                'action' => $this->action,
                'name' => $this->name ?? '',
                'middleware' => $this->middleware ?? [],
                'conditions' => $this->condition_blueprints,
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

        public function filterWpQuery(array $route_segments) : array
        {

            $callable = $this->wp_query_filter;

            if ( ! $callable) {

                return [];

            }

            return call_user_func_array($callable, $route_segments);

        }

        public function setConditionFactory(ConditionFactory $factory) {
            $this->condition_factory = $factory;
        }

        public function setActionFactory(RouteActionFactory $factory) {
            $this->action_factory = $factory;
        }

        public function hasUrlableCondition() :?UrlableInterface
        {

            return collect($this->instantiated_conditions)
                ->first(fn(ConditionInterface $condition) => $condition instanceof UrlableInterface);
        }

        public function run(Request $request, array $route_segments = [])
        {

            $condition_args = $this->conditionArgs($request);

            $payload = array_merge([$request], $route_segments, $condition_args);

            $reflection_payload = new ReflectionPayload(
                $this->instantiated_action->raw(),
                array_values($payload)
            );

            return $this->instantiated_action->executeUsing(
                $this->mergeDefaults($reflection_payload->build())
            );


        }

        public function instantiateConditions( ConditionFactory $condition_factory = null ) : Route
        {

            $factory = $condition_factory ?? $this->condition_factory;

            $this->instantiated_conditions = $factory->buildConditions($this->condition_blueprints);

            return $this;

        }

        public function instantiateAction(RouteActionFactory $action_factory = null) :Route
        {

            $factory = $action_factory ?? $this->action_factory;

            $this->instantiated_action = $factory->create($this->action, $this->namespace);

            return $this;
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

            return $this->name ?? null;

        }

        public function getUrl() : string
        {

            return $this->url;

        }

        public function getAction() {

            return $this->action;

        }

        public function getQueryFilter()
        {
            return $this->wp_query_filter;
        }

        /**
         * @param Closure|string $serialized_query_filter
         */
        public function setQueryFilter ($serialized_query_filter) {

            $this->wp_query_filter = $serialized_query_filter;

        }

        public function needsTrailingSlash() : bool
        {
            return $this->trailing_slash;
        }

        public function getSegmentNames() :array
        {
            return $this->segment_names;
        }

        public function satisfiedBy(Request $request) : bool
        {

            $failed_condition = collect($this->instantiated_conditions)
                ->first(fn($condition) => ! $condition->isSatisfied($request));

            return $failed_condition === null;

        }

        public function isFallback() :bool
        {
            return $this->action === [FallBackController::class, 'handle'];
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

            return $this->instantiated_action->resolveControllerMiddleware();

        }

        private function usesController() : bool
        {

            return ! $this->instantiated_action->raw() instanceof Closure;

        }

        private function parseUrl(string $url) : string
        {

            $url = UrlParser::replaceAdminAliases($url);

            $url = Url::addLeading($url);

            $this->segments = UrlParser::segments($url);

            $this->segment_names = UrlParser::segmentNames($url);

            return $url;

        }

        private function conditionArgs(Request $request) :array {

            $args = [];

            foreach ($this->instantiated_conditions as $condition) {

                $args = array_merge($args, $condition->getArguments($request));

            }

            return $args;

        }

        private function mergeDefaults(array $route_payload) : array
        {

            return array_merge($route_payload, $this->defaults);


        }

        private function hydrateConditionBlueprints(array $blueprints) : array
        {

            return array_map(function (array $blueprint) {

                if ( is_string($blueprint['instance']) && function_exists('\Opis\Closure\unserialize')) {

                    $blueprint['instance'] = \Opis\Closure\unserialize($blueprint['instance']);
                }

                return ConditionBlueprint::hydrate($blueprint);



            }, $blueprints);


        }


    }
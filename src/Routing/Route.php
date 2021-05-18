<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\RouteAction;
    use WPEmerge\Contracts\RouteCondition;
    use WPEmerge\Contracts\SetsRouteAttributes;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Support\Url;
    use WPEmerge\Support\UrlParser;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;
    use WPEmerge\Traits\SetRouteAttributes;

    class Route implements RouteCondition, SetsRouteAttributes
    {

        use SetRouteAttributes;

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

        /** @var RouteAction */
        private $compiled_action;

        /** @var ConditionBlueprint[] */
        private $conditions;

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

        private $regex;

        /** @var array */
        private $defaults;

        /**
         * @var RouteRegex
         */
        private $routeRegex;

        public function __construct(array $methods, string $url, $action, array $attributes = [])
        {

            $this->routeRegex = new RouteRegex();

            $this->methods = $methods;
            $this->url = $this->parseUrl($url);
            $this->action = $action;
            $this->namespace = $attributes['namespace'] ?? null;
            $this->middleware = $attributes['middleware'] ?? null;


        }

        private function parseUrl(string $url) : string
        {

            $url = UrlParser::replaceAdminAliases($url);

            $url = Url::normalizePath($url);

            return $this->routeRegex->replaceOptional($url);

        }

        public function compile() : CompiledRoute
        {

            return new CompiledRoute([
                'action' => $this->action,
                'middleware' => $this->middleware ?? [],
                'conditions' => $this->conditions ?? [],
                'namespace' => $this->namespace ?? '',
                'defaults' => $this->defaults ?? [],
                'url' => $this->url,
            ]);

        }

        public function and(...$regex) : Route
        {

            $regex_array = $this->normalizeRegex($regex);

            $this->url = $this->routeRegex->parseUrlWithRegex($regex_array, $this->url);

            /** @todo This needs to added instead of replaced regex */
            $this->regex = $regex_array;

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

        public function getRegexConstraints()
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

    }
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

        public function buildConditions(Route $route) :Route
        {

           return $route->compileConditions($this->condition_factory);

        }

        public function buildUrlableConditions(Route $route) : Route
        {

            return $this->compileConditions($route);

        }

        public function buildActions(Route $route)
        {

            $route->compileAction($this->handler_factory);

        }

        private function compileConditions(Route $route) : Route
        {

            $route->compileConditions($this->condition_factory);

            return $route;
        }


    }
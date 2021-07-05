<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware;

    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\Route;
    use BetterWP\Support\Arr;
    use BetterWP\Traits\GathersMiddleware;

    class MiddlewareStack
    {
        use GathersMiddleware;

        private $middleware_groups = [
            'web' => [],
            'admin' => [],
            'ajax' => [],
            'global'=> []
        ];

        private $route_middleware_aliases = [];

        private $middleware_priority = [];

        private $middleware_disabled = false;

        public function createFor(Route $route, Request $request) : array
        {

            if ( $this->middleware_disabled ) {
                return [];
            }

            $middleware = array_diff($route->getMiddleware(), $this->middleware_groups['global']);

            if ( $this->withGlobalMiddleware( $request ) ) {

                $middleware = $this->mergeGlobalMiddleware($middleware);

            }

            $middleware = $this->expandMiddleware($middleware);
            $middleware = $this->uniqueMiddleware($middleware);

            return $this->sortMiddleware($middleware);

        }

        public function onlyGroups ( array $groups, Request $request) : array
        {

            if ( $this->middleware_disabled ) {
                return [];
            }

            $middleware = $groups;

            if (  $this->globalMiddlewareRun($request) ) {

                Arr::pullByValue('global', $middleware);

            }

            $middleware = $this->expandMiddleware($middleware);
            $middleware = $this->uniqueMiddleware($middleware);

            return $this->sortMiddleware($middleware);

        }

        public function withMiddlewareGroup(string $group, array $middlewares)
        {

            $this->middleware_groups[$group] = $middlewares;

        }

        public function middlewarePriority( array $middleware_priority)
        {

            $this->middleware_priority = $middleware_priority;

        }

        public function middlewareAliases(array $route_middleware_aliases)
        {

            $this->route_middleware_aliases = $route_middleware_aliases;

        }

        public function disableAllMiddleware() {
            $this->middleware_disabled = true;
        }

        private function withGlobalMiddleware (Request $request) : bool
        {

            return ! $request->getAttribute('global_middleware_run', false);

        }

        private function globalMiddlewareRun(Request $request) :bool  {

            return  $request->getAttribute('global_middleware_run', false);


        }

    }
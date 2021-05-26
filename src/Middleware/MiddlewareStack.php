<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Traits\GathersMiddleware;

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

        public function createFor(Route $route, Request $request) : array
        {

            $middleware = $route->getMiddleware();

            if ( $this->withGlobalMiddleware($request) ) {

                $middleware = $this->mergeGlobalMiddleware($middleware);

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

        private function withGlobalMiddleware (Request $request) {

            return ! $request->getAttribute('global_middleware_run', false);

        }

    }
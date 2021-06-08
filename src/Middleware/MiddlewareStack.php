<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Http\Psr7\Request;
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

        private $unique_middleware = [];

        private $run_count = 0;

        public function createFor(Route $route, Request $request) : array
        {

            $middleware = $route->getMiddleware();

            if ( $this->withGlobalMiddleware( $request ) ) {

                $middleware = $this->mergeGlobalMiddleware($middleware);

            }

            $middleware = $this->expandMiddleware($middleware);
            $middleware = $this->uniqueMiddleware($middleware);

            $middleware = $this->run_count < 1
                ? $middleware
                : $this->onlyNonUnique($middleware);

            $this->run_count++;

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

        public function withUniqueMiddleware ( array $unique_middleware) {

            $this->unique_middleware =$unique_middleware;

        }

        private function withGlobalMiddleware (Request $request) : bool
        {

            return ! $request->getAttribute('global_middleware_run', false);

        }

        private function onlyNonUnique(array $middleware) : array
        {

            return collect($middleware)->reject(function (array $middleware) {

                return in_array($middleware[0], $this->unique_middleware);

            })->all();

        }

    }
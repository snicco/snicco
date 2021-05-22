<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Contracts\ContainerAdapter;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Traits\GathersMiddleware;

    class RouteRunner extends Middleware
    {

        use GathersMiddleware;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var Pipeline
         */
        private $pipeline;

        private $middleware_groups = [
            'web' => [],
            'admin' => [],
            'ajax' => [],
            'global'=> []
        ];

        private $route_middleware_aliases = [];

        private $middleware_priority = [];


        public function __construct(ResponseFactory $response_factory, Pipeline $pipeline)
        {

            $this->response_factory = $response_factory;
            $this->pipeline = $pipeline;

        }

        public function handle(Request $request, Delegate $next)
        {

            /** @var RoutingResult $route_result */
            $route_result = $request->getAttribute('route_result');

            $include_global_middleware = ! $request->getAttribute('global_middleware_run', false);

            if ( ! $route = $route_result->route()) {

                return $this->response_factory->null();

            }

            $middleware_stack = $this->middlewareStack($route, $include_global_middleware);

            return $this->pipeline
                ->send($request)
                ->through($middleware_stack)
                ->then($this->runRoute($route_result));


        }

        private function runRoute(RoutingResult $routing_result) : \Closure
        {

            return function (Request $request) use ($routing_result) {

                $response = $routing_result->route()->run(
                    $request,
                    $routing_result->capturedUrlSegmentValues()
                );

                return $this->response_factory->toResponse($response);

            };

        }

        private function middlewareStack(Route $route, bool $with_global_middleware) : array
        {

            $middleware = $route->getMiddleware();
            $middleware = $this->expandMiddleware($middleware);

            if ($with_global_middleware) {

                $middleware = $this->mergeGlobalMiddleware($middleware);

            }

            $middleware = $this->uniqueMiddleware($middleware);
            $middleware = $this->sortMiddleware($middleware);

            return $middleware;

        }

        public function withMiddlewareGroup(string $group, array $middlewares)
        {

            $this->middleware_groups[$group] = $middlewares;

        }

        public function middlewarePriority(array $middleware_priority)
        {

            $this->middleware_priority = $middleware_priority;

        }

        public function middlewareAliases(array $route_middleware_aliases)
        {

            $this->route_middleware_aliases = $route_middleware_aliases;

        }


    }
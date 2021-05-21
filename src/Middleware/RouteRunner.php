<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

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
         * @var ContainerAdapter
         */
        private $container;

        private $middleware_groups = [];

        private $route_middleware_aliases = [];

        private $middleware_priority = [];

        public function __construct(ResponseFactory $response_factory, ContainerAdapter $container)
        {

            $this->response_factory = $response_factory;
            $this->container = $container;

        }

        public function handle(Request $request, Delegate $next)
        {

            /** @var RoutingResult $route_result */
            $route_result = $request->getAttribute('route_result');

            if ( ! $route = $route_result->route()) {

                return $this->response_factory->null();

            }

            $url_segments = $route_result->capturedUrlSegmentValues();
            $middleware_stack = $this->middlewareStack($route);
            $pipeline = new Pipeline($this->container);

            return $pipeline
                ->send($request)
                ->through($middleware_stack)
                ->then(function (Request $request) use ($url_segments, $route) {

                    $response = $route->run($request, $url_segments);

                    return $this->response_factory->toResponse($response);

                });


        }

        private function middlewareStack(Route $route) : array
        {

            $middleware = $route->getMiddleware();
            $middleware = $this->expandMiddleware($middleware);
            $middleware = $this->mergeGlobalMiddleware($middleware);
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
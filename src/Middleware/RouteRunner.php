<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Contracts\ContainerAdapter;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Pipeline;
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

        private $middleware_groups = [

            'web' => [

            ],
            'admin' => [

            ],
            'ajax' => [

            ]

        ];

        private $route_middleware_aliases = [
            'guest' => RedirectIfAuthenticated::class,
            'auth' => Authenticate::class
        ];

        private $middleware_priority = [

        ];

        public function __construct(ResponseFactory $response_factory, ContainerAdapter $container)
        {

            $this->response_factory = $response_factory;
            $this->container = $container;

        }

        public function handle(Request $request, Delegate $next)
        {

            /** @var RoutingResult $route_result */
            $route_result = $request->getAttribute('route_result');

            if ( ! $route = $route_result->route() ) {

                return $this->response_factory->null();

            }

            $url_segments = $route_result->capturedUrlSegmentValues();

            $middleware = $route->getMiddleware();
            $middleware = $this->expandMiddleware($middleware);
            $middleware = $this->uniqueMiddleware($middleware);
            $middleware = $this->sortMiddleware($middleware);

            return ((new Pipeline($this->container)))
                ->send($request)
                ->through($middleware)
                ->then(function (Request $request) use ( $url_segments, $route ) {

                    $response = $route->run($request, $url_segments);

                    return $this->response_factory->toResponse($response);

                });


        }

    }
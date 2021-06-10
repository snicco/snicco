<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Closure;
    use Contracts\ContainerAdapter;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\MiddlewareStack;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;

    class RouteRunner extends Middleware
    {

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var Pipeline
         */
        private $pipeline;

        /**
         * @var MiddlewareStack
         */
        private $middleware_stack;

        /**
         * @var ContainerAdapter
         */
        private $container;

        public function __construct(ResponseFactory $response_factory, ContainerAdapter $container, Pipeline $pipeline, MiddlewareStack $middleware_stack)
        {

            $this->response_factory = $response_factory;
            $this->pipeline = $pipeline;
            $this->middleware_stack = $middleware_stack;
            $this->container = $container;

        }

        public function handle(Request $request, Delegate $next)
        {

            $this->rebindRequest($request);

            $route_result = $request->routingResult();

            if ( ! $route = $route_result->route()) {

                return $this->response_factory->null();

            }

            if ( $route->isFallback() ) {

                return $this->runFallbackRouteController($route, $request);

            }

            $middleware = $this->middleware_stack->createFor($route, $request);


            return $this->pipeline
                ->send($request)
                ->through($middleware)
                ->then($this->runRoute($route_result));


        }

        private function runRoute(RoutingResult $routing_result) : Closure
        {

            return function (Request $request) use ($routing_result) {

                $response = $routing_result->route()->run(
                    $request,
                    $routing_result->capturedUrlSegmentValues()
                );

                return $this->response_factory->toResponse($response);

            };

        }

        private function runFallbackRouteController(Route $route, Request $request) : Response
        {

            return $this->response_factory->toResponse($route->run($request));

        }

        /**
         *
         * In order to allow running global routes early on the init hook
         * we need to rebind the request class for routes that run on template_include
         * or admin_init.
         *
         * If we dont rebind the request we have to run all Middleware that decorates the request again.
         *
         * @param  Request  $request
         */
        private function rebindRequest(Request $request)
        {
            $this->container->instance(Request::class, $request);
        }

    }
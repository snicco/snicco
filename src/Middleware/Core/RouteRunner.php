<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Closure;
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

        public function __construct(ResponseFactory $response_factory, Pipeline $pipeline, MiddlewareStack $middleware_stack)
        {

            $this->response_factory = $response_factory;
            $this->pipeline = $pipeline;
            $this->middleware_stack = $middleware_stack;

        }

        public function handle(Request $request, Delegate $next)
        {

            $route_result = $request->getRoutingResult();

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

    }
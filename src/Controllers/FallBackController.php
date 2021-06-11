<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Middleware\MiddlewareStack;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\Route;
    use WPEmerge\Traits\GathersMiddleware;

    /**
     *
     * This class is the the default route handler for ALL routes that
     * do not have a URL-Constraint specified but instead rely on WordPress conditional tags.
     *
     * We cant match these routes with FastRoute so this Controller will figure out if we
     * have a matching route.
     *
     */
    class FallBackController
    {

        use GathersMiddleware;

        /**
         * @var ResponseFactory
         */
        private $response;

        /**
         * @var callable
         */
        private $fallback_handler;
        /**
         * @var Pipeline
         */
        private $pipeline;
        /**
         * @var MiddlewareStack
         */
        private $middleware_stack;

        public function __construct(ResponseFactory $response, Pipeline $pipeline, MiddlewareStack $middleware_stack) {

            $this->response = $response;
            $this->pipeline = $pipeline;
            $this->middleware_stack = $middleware_stack;

        }

        public function handle(Request $request, AbstractRouteCollection $routes)
        {

            $possible_routes = collect($routes->withWildCardUrl( $request->getMethod() ) );

            /** @var Route $route */
            $route = $possible_routes->first(function (Route $route) use ($request) {

                $route->instantiateConditions();

                return $route->satisfiedBy($request);

            });

            if ( ! $route ) {

                return ($this->fallback_handler)
                    ? call_user_func($this->fallback_handler, $request)
                    : $this->response->null();

            }

            $route->instantiateAction();

            $middleware = $this->middleware_stack->createFor($route, $request);

            return $this->pipeline
                ->send($request)
                ->through($middleware)
                ->then($this->runRoute($route));


        }

        public function blankResponse() : NullResponse
        {

            return $this->response->queryFiltered();

        }

        public function setFallbackHandler(callable $fallback_handler)
        {
            $this->fallback_handler = $fallback_handler;
        }

        private function runRoute(Route $route ) : \Closure
        {

            return function ( Request $request ) use ($route) {

                $response = $route->run($request);

                return $this->response->toResponse($response);

            };

        }

    }
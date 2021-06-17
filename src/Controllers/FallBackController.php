<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use Closure;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\AbstractRouteCollection as Routes;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Middleware\MiddlewareStack;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\Route;

    /**
     *
     * This class is the the default route handler for ALL routes that
     * do not have a URL-Constraint specified but instead rely on WordPress conditional tags.
     *
     * We cant match these routes with FastRoute so this Controller will figure out if we
     * have a matching route.
     *
     */
    class FallBackController extends Controller
    {


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

        /** @var Closure */
        private $respond_with;

        public function __construct(Pipeline $pipeline, MiddlewareStack $middleware_stack) {

            $this->pipeline = $pipeline;
            $this->middleware_stack = $middleware_stack;

        }

        public function handle(Request $request, Routes $routes) :ResponseInterface
        {

            $possible_routes = collect($routes->withWildCardUrl( $request->getMethod() ) );

            /** @var Route $route */
            $route = $possible_routes->first(function (Route $route) use ($request) {

                $route->instantiateConditions();

                return $route->satisfiedBy($request);

            });


            if ( $route ) {

                $this->respond_with = $this->runRoute($route);
                $route->instantiateAction();

            } else {

                $this->respond_with = $this->nonMatchingRoute();

            }

            $middleware = $route
                ? $this->middleware_stack->createFor($route, $request)
                : $this->middleware_stack->onlyGroups($this->fallback_handler ? ['global', 'web'] : ['web'], $request);

            return $this->pipeline
                ->send($request)
                ->through($middleware)
                ->then(function (Request $request) {

                    $response = call_user_func($this->respond_with, $request);
                    return $this->response_factory->toResponse($response);

                });


        }

        public function blankResponse() : NullResponse
        {

            return $this->response_factory->queryFiltered();

        }

        public function setFallbackHandler(callable $fallback_handler)
        {
            $this->fallback_handler = $fallback_handler;
        }

        private function runRoute(Route $route ) : Closure
        {

            return function ( Request $request ) use ($route) {

                $response = $route->run($request);

                return $this->response_factory->toResponse($response);

            };

        }

        private function nonMatchingRoute() : Closure
        {

            return function ( Request $request )  {

                return ($this->fallback_handler)
                    ? call_user_func($this->fallback_handler, $request)
                    : $this->response_factory->null();

            };

        }

    }
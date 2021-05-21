<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Closure;
    use Contracts\ContainerAdapter as Container;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Events\DoShutdown;
    use WPEmerge\Events\FilterWpQuery;
    use WPEmerge\Events\HeadersSent;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\BodySent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidResponseException;
    use WPEmerge\Middleware\ErrorHandlerMiddleware;
    use WPEmerge\Middleware\OutputBufferMiddleware;
    use WPEmerge\Middleware\RoutingMiddleware;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Middleware\RouteRunner;
    use WPEmerge\Traits\GathersMiddleware;

    class HttpKernel
    {

        use GathersMiddleware;

        /** @var Container */
        private $container;

        /** @var Response */
        private $response;

        /**
         * @var AbstractRouteCollection
         */
        private $routes;



        /**
         * @var ResponseEmitter
         */
        private $response_emitter;

        private $is_test_mode = false;

        /**
         * @var bool
         */
        private $always_with_global_middleware = false;

        private $internal_middleware = [
            ErrorHandlerMiddleware::class,
            RoutingMiddleware::class,
            RouteRunner::class,
        ];

        private $middleware_priority = [

            ErrorHandlerMiddleware::class,
            OutputBufferMiddleware::class,
            RoutingMiddleware::class,
            RouteRunner::class,

        ];


        public function __construct(Container $container, AbstractRouteCollection $routes)
        {

            $this->container = $container;
            $this->response_emitter = new ResponseEmitter();
            $this->routes = $routes;

        }

        public function runGlobal(ServerRequestInterface $request)
        {

            $middleware = $this->middleware_groups['global'] ?? [];

            /** @var Response $response */
            $response = (new Pipeline($this->container))
                ->send($request)
                ->through($middleware)
                ->then(function (Request $request, ResponseFactory $response) {

                    $this->container->instance(Request::class, $request);

                    return $response->null();

                });

            if ($response instanceof NullResponse) {

                return;

            }

            $response = $this->returnIfValid($response);

            $this->response_emitter->emit($response);

            ResponseSent::dispatch([
                $request->withAttribute('from_global_middleware', true),
                $request,
            ]);


        }

        public function run(IncomingRequest $request_event) : void
        {

            $this->response = $this->handle($request_event);

            if ($this->matchedRoute()) {

                $request_event->matchedRoute();

            }

            if ($this->response instanceof NullResponse) {

                return;

            }

            $this->response_emitter->emit($this->response);

            ResponseSent::dispatch(
                [
                    $this->container->make(Request::class),
                    $this->response,

                ]);


        }

        public function runInTestMode() : void
        {

            $this->is_test_mode = true;

        }

        public function alwaysWithGlobalMiddleware()
        {

            $this->always_with_global_middleware = true;

        }

        public function filterRequest(FilterWpQuery $event)
        {

            $match = $this->routes->match($event->server_request);

            if ($match->route()) {

                return $match->route()->filterWpQuery(
                    $event->currentQueryVars(),
                    $match->capturedUrlSegmentValues()
                );

            }

            return $event->currentQueryVars();

        }

        private function handle(IncomingRequest $request_event) : Response
        {

            $this->container->instance(Request::class, $request = $request_event->request);

            $pipeline = new Pipeline($this->container);

            $middleware = $this->gatherMiddleware($request_event);

            /** @var Response $response */
            $response = $pipeline->send($request)
                                 ->through($middleware)
                                 ->run();

            return $this->returnIfValid($response);

        }

        private function gatherMiddleware(IncomingRequest $incoming_request) : array
        {

            $middleware = $this->internal_middleware;

            if ( ! $this->withMiddleware()) {

                return $middleware;

            }

            if ($incoming_request instanceof IncomingAdminRequest) {

                $middleware[] = OutputBufferMiddleware::class;

            }

            $middleware = array_merge($middleware, $this->middleware_groups['global'] ?? []);

            return $this->sortMiddleware($middleware);


        }

        private function withMiddleware() : bool
        {

            return ! $this->is_test_mode && $this->always_with_global_middleware;

        }

        private function returnIfValid(Response $response) : Response
        {

            // We had no matching route.
            if ($response instanceof NullResponse) {

                return $response;

            }

            // We had a route action return something but it was not transformable to a Psr7 Response.
            if ($response instanceof InvalidResponse) {

                throw new InvalidResponseException(
                    'The response returned by the route action is not valid.'
                );

            }

            return $response;

        }

        private function matchedRoute() : bool
        {

            return ! $this->response instanceof NullResponse;
        }


    }

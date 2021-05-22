<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Contracts\ContainerAdapter as Container;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Middleware\ErrorHandlerMiddleware;
    use WPEmerge\Middleware\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\OutputBufferMiddleware;
    use WPEmerge\Middleware\RoutingMiddleware;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Middleware\RouteRunner;
    use WPEmerge\ServiceProviders\MiddlewareServiceProvider;
    use WPEmerge\Support\Arr;
    use WPEmerge\Traits\SortsMiddleware;

    class HttpKernel
    {

        use SortsMiddleware;

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

        private $core_middleware = [
            ErrorHandlerMiddleware::class,
            EvaluateResponseMiddleware::class,
            OutputBufferMiddleware::class,
            RoutingMiddleware::class,
            RouteRunner::class,
        ];

        // Only these two get a priority, because they always need to run before any global middleware
        // that a user might provide.
        private $priority_map = [
            ErrorHandlerMiddleware::class,
            EvaluateResponseMiddleware::class,
        ];

        private $global_middleware = [];

        public function __construct(Container $container, AbstractRouteCollection $routes)
        {

            $this->container = $container;
            $this->response_emitter = new ResponseEmitter();
            $this->routes = $routes;

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

        private function handle(IncomingRequest $request_event) : ResponseInterface
        {

            $this->container->instance(Request::class, $request = $request_event->request);

            $pipeline = new Pipeline($this->container);

            return $pipeline->send($request)
                            ->through($this->gatherMiddleware($request_event))
                            ->run();


        }

        public function runInTestMode() : void
        {

            $this->is_test_mode = true;

        }

        public function alwaysWithGlobalMiddleware(array $global_middleware = [])
        {

            $this->global_middleware = $global_middleware;
            $this->always_with_global_middleware = true;

        }

        private function gatherMiddleware(IncomingRequest $incoming_request) : array
        {

            if ( ! $incoming_request instanceof IncomingAdminRequest) {

                Arr::pullByValue(OutputBufferMiddleware::class, $this->core_middleware);

            }

            if ( ! $this->withMiddleware()) {

                return $this->core_middleware;

            }

            $merged = array_merge($this->global_middleware, $this->core_middleware);

            $this->container->instance(MiddlewareServiceProvider::GLOBAL_MIDDLEWARE_ALREADY_HANDLED, true);

            return $this->sortMiddleware($merged, $this->priority_map);


        }

        private function withMiddleware() : bool
        {

            return ! $this->is_test_mode && $this->always_with_global_middleware;

        }

        private function matchedRoute() : bool
        {

            return ! $this->response instanceof NullResponse;
        }



    }

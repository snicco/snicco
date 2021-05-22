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
    use WPEmerge\Middleware\Core\ErrorHandlerMiddleware;
    use WPEmerge\Middleware\Core\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Middleware\Core\RoutingMiddleware;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\ServiceProviders\MiddlewareServiceProvider;
    use WPEmerge\Support\Arr;
    use WPEmerge\Traits\SortsMiddleware;

    class HttpKernel
    {

        use SortsMiddleware;

        /** @var Response */
        private $response;

        /**
         * @var AbstractRouteCollection
         */
        private $routes;

        /**
         * @var Pipeline
         */
        private $pipeline;

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

        public function __construct(Pipeline $pipeline, AbstractRouteCollection $routes)
        {

            $this->pipeline = $pipeline;
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

            ResponseSent::dispatch([$this->response]);


        }

        private function handle(IncomingRequest $request_event) : ResponseInterface
        {

            $request = $request_event->request;

            if ( $this->withMiddleware() ) {

                $request = $request->withAttribute('global_middleware_run', true);

            }

            return $this->pipeline->send($request)
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

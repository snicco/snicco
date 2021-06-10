<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Middleware\Core\AppendSpecialPathSuffix;
    use WPEmerge\Middleware\Core\ErrorHandlerMiddleware;
    use WPEmerge\Middleware\Core\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\Core\MethodOverride;
    use WPEmerge\Middleware\Core\RoutingMiddleware;
    use WPEmerge\Middleware\Core\SetRequestAttributes;
    use WPEmerge\Middleware\Core\ShareCookies;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Traits\SortsMiddleware;

    class HttpKernel
    {

        use SortsMiddleware;

        /**
         * @var Pipeline
         */
        private $pipeline;

        private $is_test_mode = false;

        /**
         * @var bool
         */
        private $always_with_global_middleware = false;

        private $core_middleware = [
            ErrorHandlerMiddleware::class,
            SetRequestAttributes::class,
            MethodOverride::class,
            EvaluateResponseMiddleware::class,
            ShareCookies::class,
            AppendSpecialPathSuffix::class,
            RoutingMiddleware::class,
            RouteRunner::class,
        ];

        private $unique_middleware = [
            MethodOverride::class,
        ];

        // Only these get a priority, because they always need to run before any global middleware
        // that a user might provide.
        private $priority_map = [
            ErrorHandlerMiddleware::class,
            SetRequestAttributes::class,
            EvaluateResponseMiddleware::class,
            ShareCookies::class,
            AppendSpecialPathSuffix::class,
        ];

        private $global_middleware = [];

        /**
         * @var ResponseEmitter
         */
        private $emitter;

        /**
         * @var int
         */
        private $run_count = 0;

        public function __construct(Pipeline $pipeline, ResponseEmitter $emitter = null)
        {

            $this->pipeline = $pipeline;
            $this->emitter = $emitter ?? new ResponseEmitter();


        }

        public function alwaysWithGlobalMiddleware(array $global_middleware = [])
        {

            $this->global_middleware = $global_middleware;
            $this->always_with_global_middleware = true;
        }

        public function addUniqueMiddlewares(array $unique_middleware)
        {

            $this->unique_middleware = array_merge($this->unique_middleware, $unique_middleware);

        }

        public function run(IncomingRequest $request_event) : void
        {

            $response = $this->handle($request_event);

            if ($response instanceof NullResponse) {

                return;

            }

            $request_event->matchedRoute();

            $this->emitter->emit($response);

            ResponseSent::dispatch([$response, $request_event->request]);


        }

        private function handle(IncomingRequest $request_event) : ResponseInterface
        {

            $request = $request_event->request;

            if ( $this->withMiddleware() ) {

                $request = $request->withAttribute('global_middleware_run', true);

            }

            $response = $this->pipeline->send($request)
                                       ->through($this->gatherMiddleware())
                                       ->run();
            $this->run_count++;

            return $response;


        }

        private function gatherMiddleware() : array
        {

            if ( ! $this->withMiddleware() ) {

                return $this->core_middleware;

            }

            $global = $this->run_count < 1
                ? $this->global_middleware
                : $this->onlyNonUnique($this->global_middleware);

            $core = $this->run_count < 1
                ? $this->core_middleware
                : $this->onlyNonUnique($this->core_middleware);

            $merged = array_merge($global, $core);

            return $this->sortMiddleware($merged, $this->priority_map);


        }

        private function onlyNonUnique(array $middleware) : array
        {

            return array_values(array_diff($middleware, $this->unique_middleware));

        }

        private function withMiddleware() : bool
        {

            return ! $this->is_test_mode && $this->always_with_global_middleware;

        }


    }

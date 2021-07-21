<?php


    declare(strict_types = 1);


    namespace Snicco\Http;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Events\IncomingAdminRequest;
    use Snicco\Events\IncomingRequest;
    use Snicco\Events\ResponseSent;
    use Snicco\Http\Psr7\Response;
    use Snicco\Http\Responses\NullResponse;
    use Snicco\Middleware\Core\AppendSpecialPathSuffix;
    use Snicco\Middleware\Core\ErrorHandlerMiddleware;
    use Snicco\Middleware\Core\EvaluateResponseMiddleware;
    use Snicco\Middleware\Core\MethodOverride;
    use Snicco\Middleware\Core\OutputBufferMiddleware;
    use Snicco\Middleware\Core\RoutingMiddleware;
    use Snicco\Middleware\Core\SetRequestAttributes;
    use Snicco\Middleware\Core\ShareCookies;
    use Snicco\Routing\Pipeline;
    use Snicco\Middleware\Core\RouteRunner;
    use Snicco\Support\Arr;
    use Snicco\Traits\SortsMiddleware;

    class HttpKernel
    {

        use SortsMiddleware;

        /**
         * @var Pipeline
         */
        private $pipeline;

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
            OutputBufferMiddleware::class,
            RoutingMiddleware::class,
            RouteRunner::class,
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

        public function withPriority(array $priority)
        {

            $this->priority_map = array_merge($this->priority_map, $priority);
        }

        public function run(IncomingRequest $request_event) : ResponseInterface
        {

            $response = $this->handle($request_event);

            if ($response instanceof NullResponse) {

                // We might have a NullResponse where the headers got modified by middleware.
                $this->emitter->emitHeaders($response);

                return $response;

            }

            $request_event->matchedRoute();

            $this->emitter->emit($response);

            ResponseSent::dispatch([$response, $request_event->request]);

            return $response;

        }

        private function handle(IncomingRequest $request_event) : ResponseInterface
        {

            $request = $request_event->request;

            if ($this->withMiddleware()) {

                $request = $request->withAttribute('global_middleware_run', true);

            }

            return $this->pipeline->send($request)
                                  ->through($this->gatherMiddleware($request_event))
                                  ->run();


        }

        private function gatherMiddleware(IncomingRequest $event) : array
        {

            if ( ! $event instanceof IncomingAdminRequest) {

                Arr::pullByValue(OutputBufferMiddleware::class, $this->core_middleware);

            }

            if ( ! $this->withMiddleware() ) {

                return $this->core_middleware;

            }

            $merged = array_merge($this->global_middleware, $this->core_middleware);

            return $this->sortMiddleware($merged, $this->priority_map);


        }

        private function withMiddleware() : bool
        {

            return $this->always_with_global_middleware;

        }


    }


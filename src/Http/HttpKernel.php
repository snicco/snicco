<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Closure;
    use Contracts\ContainerAdapter as Container;
    use Psr\Http\Message\ResponseInterface;
    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface as ErrorHandler;
    use WPEmerge\Events\HeadersSent;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\BodySent;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidResponseException;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Traits\HoldsMiddlewareDefinitions;

    class HttpKernel
    {

        use HoldsMiddlewareDefinitions;

        /** @var Router */
        private $router;

        /** @var ErrorHandler */
        private $error_handler;

        /** @var Container */
        private $container;

        /** @var Response */
        private $response;

        /**
         * @var HttpResponseFactory
         */
        private $response_factory;

        /**
         * @var ResponseEmitter
         */
        private $response_emitter;

        private $is_test_mode = false;

        /**
         * @var bool
         */
        private $always_with_global_middleware = false;

        public function __construct(

            Router $router,
            Container $container,
            /** @todo this could be a middleware */
            ErrorHandler $error_handler
        ) {

            $this->router = $router;
            $this->container = $container;
            $this->error_handler = $error_handler;
            $this->response_emitter = new ResponseEmitter();

        }


        public function handle(IncomingRequest $request_event) : void
        {

            $this->error_handler->register();

            try {

                $this->syncMiddlewareToRouter();

                $this->response = $this->sendRequestThroughRouter($request_event->request);

            }

            catch (Throwable $exception) {

                $this->response = $this->error_handler->transformToResponse($exception);

            }

            if ($this->matchedRoute()) {

                $request_event->matchedRoute();

            }

            $this->sendResponse();

            $this->error_handler->unregister();

        }

        /**
         * This function needs to be public because for Wordpress Admin
         * pages we have to send the header and body on separate hooks.
         * ( sucks. but it is what it is )
         */
        public function sendBodyDeferred()
        {

            // guard against AdminBodySendable for non matching admin pages.
            if ( ! $this->response instanceof ResponseInterface) {

                return;

            }

            $request = $this->container->make(Request::class);

            $this->sendBody($request);

        }

        public function runInTestMode() : void
        {

            $this->is_test_mode = true;

        }

        private function sendResponse()
        {

            if ($this->response instanceof NullResponse) {

                return;

            }


            $request = $this->container->make(Request::class);

            $this->sendHeaders($request);

            if ($request->getType() !== IncomingAdminRequest::class) {

                $this->sendBody($request);

            }

        }

        private function sendHeaders(Request $request)
        {

            $this->response_emitter->emitHeaders($this->response);

            HeadersSent::dispatch([$this->response, $request]);

        }

        private function sendBody(Request $request)
        {

            $this->response_emitter->emitBody($this->response);

            BodySent::dispatch([$this->response, $request]);

        }

        private function sendRequestThroughRouter(Request $request) : Response
        {

            $pipeline = new Pipeline($this->container);

            $middleware = $this->withMiddleware() ? $this->middleware_groups['global'] ?? [] : [];

            /** @var Response $response */
            $response = $pipeline->send($request)
                                 ->through($middleware)
                                 ->then($this->dispatchToRouter());

            return $this->returnIfValid($response);

        }

        private function dispatchToRouter() : Closure
        {

            return function (Request $request) : ResponseInterface {

                $this->container->instance(Request::class, $request);

                if ($this->is_test_mode) {

                    $this->router->withoutMiddleware();

                }

                return $this->router->runRoute($request);


            };

        }

        private function syncMiddlewareToRouter() : void
        {


            $this->router->middlewarePriority($this->middleware_priority);

            $middleware_groups = $this->middleware_groups;

            // Dont run global middleware in the router again.
            if ($this->always_with_global_middleware) {

                unset($middleware_groups['global']);

            }

            foreach ($middleware_groups as $key => $middleware) {

                $this->router->middlewareGroup($key, $middleware);

            }

            foreach ($this->route_middleware_aliases as $key => $middleware) {

                $this->router->aliasMiddleware($key, $middleware);

            }

        }

        private function withMiddleware() : bool
        {

            return ! $this->is_test_mode && $this->always_with_global_middleware;

        }

        private function returnIfValid(Response $response) : Response
        {

            // We had no matching route.
            if ( $response instanceof NullResponse) {

                return $response;

            }

            // We had a route action return something but it was not transformable to a Psr7 Response.
            if ( $response instanceof InvalidResponse ) {

                throw new InvalidResponseException(
                    'The response returned by the route action is not valid.'
                );

            }

            return $response;

        }

        public function alwaysWithGlobalMiddleware()
        {

            $this->always_with_global_middleware = true;

        }

        private function matchedRoute() : bool
        {

            return ! $this->response instanceof NullResponse && $this->response->getStatusCode() === 200;
        }


    }

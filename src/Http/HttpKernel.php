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
    use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Exceptions\InvalidResponseException;
    use WPEmerge\Routing\Router;
    use WPEmerge\Support\Pipeline;
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

        /** @var Request */
        private $request;

        private $is_test_mode = false;

        /** @var bool @todo Split this up into always run global middleware and force route match. */
        private $is_takeover_mode = false;

        /**
         * @var HttpResponseFactory
         */
        private $response_factory;

        /**
         * @var ResponseEmitter
         */
        private $response_emitter;


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

            if ($this->forceRouteMatch()) {

                $request_event->enforceRouteMatch();

            }

            try {

                $this->syncMiddlewareToRouter();

                $this->response = $this->sendRequestThroughRouter($request_event->request);

            }

            catch (Throwable $exception) {

                $this->response = $this->error_handler->transformToResponse($exception);

            }

            if ( $this->response instanceof NullResponse) {

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

            $this->sendBody();

        }

        public function runInTestMode() : void
        {

            $this->is_test_mode = true;

        }

        public function runInTakeoverMode() : void
        {

            $this->is_takeover_mode = true;

        }

        private function sendResponse()
        {

            if ( $this->response instanceof NullResponse) {

                return;

            }

            $this->sendHeaders();

            if ( $this->request->getType() !== IncomingAdminRequest::class) {

                $this->sendBody();

            }

        }

        private function sendHeaders()
        {

            $this->response_emitter->emitHeaders($this->response);

            HeadersSent::dispatch([$this->response, $this->request]);

        }

        private function sendBody()
        {

            $this->response_emitter->emitBody($this->response);

            BodySent::dispatch([$this->response, $this->request]);

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

                $this->container->instance(RequestInterface::class, $request);

                $this->request = $request;

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
            if ($this->runGlobalMiddlewareWithoutMatchingRoute()) {

                unset($middleware_groups['global']);

            }

            foreach ($middleware_groups as $key => $middleware) {

                $this->router->middlewareGroup($key, $middleware);

            }

            foreach ($this->route_middleware_aliases as $key => $middleware) {

                $this->router->aliasMiddleware($key, $middleware);

            }

        }

        private function runGlobalMiddlewareWithoutMatchingRoute() : bool
        {

            return $this->is_takeover_mode;

        }

        private function withMiddleware() : bool
        {

            return ! $this->is_test_mode && $this->runGlobalMiddlewareWithoutMatchingRoute();

        }

        private function forceRouteMatch() : bool
        {

            return $this->is_takeover_mode;

        }

        private function returnIfValid( Response $response ) : Response
        {

            if ( ! $response instanceof NullResponse ) {

                return $response;

            }

            if ($this->is_takeover_mode) {

                throw new InvalidResponseException(
                    'The response by the route action is not valid.'
                );

            }

            return $response;

        }


    }

<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling;

    use Contracts\ContainerAdapter;
    use Psr\Log\LoggerInterface;
    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Events\UnrecoverableExceptionHandled;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Session\Session;
    use WPEmerge\Traits\HandlesExceptions;
    use WPEmerge\Validation\Exceptions\ValidationException;

    class ProductionErrorHandler implements ErrorHandlerInterface
    {

        use HandlesExceptions;

        /**
         * @var bool
         */
        protected $is_ajax;

        /**
         * @var ContainerAdapter
         */
        protected $container;

        /**
         * @var LoggerInterface
         */
        protected $logger;

        /**
         * @var array
         */
        protected $dont_report = [];

        /**
         * @var ResponseFactory
         */
        protected $response;

        public function __construct(ContainerAdapter $container, LoggerInterface $logger, ResponseFactory $response_factory, bool $is_ajax)
        {

            $this->is_ajax = $is_ajax;
            $this->container = $container;
            $this->logger = $logger;
            $this->response = $response_factory;

        }

        public function handleException($exception, $in_routing_flow = false, ?Request $request = null)
        {

            $this->logException($exception);

            $response = $this->createResponseObject(
                $exception,
                $request ?? $this->resolveRequestFromContainer()
            );

            if ($in_routing_flow) {

                return $response;

            }

            (new ResponseEmitter())->emit($response);

            // Shut down the script
            UnrecoverableExceptionHandled::dispatch();

        }

        public function transformToResponse(Throwable $exception, Request $request) : ?Response
        {

            return $this->handleException($exception, true, $request);

        }

        public function unrecoverable(Throwable $exception)
        {

            $this->handleException($exception);
        }

        /**
         *
         * Override this method from a child class to create
         * your own globalContext.
         *
         * @return array
         */
        protected function globalContext() : array
        {

            try {
                return array_filter([
                    'user_id' => WP::userId(),
                ]);
            }
            catch (Throwable $e) {
                return [];
            }

        }

        /**
         *
         * Override this method from a child class to create
         * your own default response for fatal errors that can not be transformed by this error
         * handler.
         *
         * @param  Request  $request
         *
         * @return Response
         */
        protected function defaultResponse(Request $request) : Response
        {

            if ($request->isExpectingJson()) {

                return $this->response->json('Internal Server Error', 500);

            }

            return $this->response->error(new HttpException(500, 'Internal Server Error'));

        }

        private function createResponseObject(Throwable $e, Request $request) : Response
        {

            if (method_exists($e, 'render')) {

                /** @var Response $response */
                $response = $this->container->call([$e, 'render'], ['request' => $request]);

                if ( ! $response instanceof Response) {

                    return $this->defaultResponse($request);

                }

                return $response;

            }


            if ($e instanceof HttpException) {

                return $this->renderHttpException($e, $request);

            }

            return $this->defaultResponse($request);


        }

        private function logException(Throwable $exception)
        {

            if (in_array(get_class($exception), $this->dont_report)) {

                return;

            }

            if (method_exists($exception, 'report')) {

                if ($this->container->call([$exception, 'report']) === false) {

                    return;

                }

            }

            $this->logger->error(
                $exception->getMessage(),
                array_merge(
                    $this->globalContext(),
                    $this->exceptionContext($exception),
                    ['exception' => $exception]
                )
            );

        }

        private function exceptionContext(Throwable $e)
        {

            if (method_exists($e, 'context')) {
                return $e->context();
            }

            return [];
        }

        private function renderHttpException(HttpException $http_exception, Request $request) : Response
        {

            if ($request->isExpectingJson()) {

                return $this->response->json($http_exception->jsonMessage(), $http_exception->getStatusCode());

            }

            $http_exception = $http_exception->causedBy($request);

            return $this->response->error($http_exception);

        }

        private function renderValidationException(ValidationException $e)
        {

            $errors = $e->getErrors();

        }


    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling;

    use Contracts\ContainerAdapter;
    use Illuminate\Support\Facades\Http;
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
    use WPEmerge\Support\Arr;
    use WPEmerge\Traits\HandlesExceptions;
    use WPEmerge\Validation\Exceptions\ValidationException;

    class ProductionErrorHandler implements ErrorHandlerInterface
    {

        use HandlesExceptions;

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
        protected $dont_report = [
            ValidationException::class,
        ];

        protected $dont_flash = [

        ];

        /**
         * @var ResponseFactory
         */
        protected $response;

        protected $fallback_error_message = 'Internal Server Error';

        public function __construct(ContainerAdapter $container, LoggerInterface $logger, ResponseFactory $response_factory)
        {

            $this->container = $container;
            $this->logger = $logger;
            $this->response = $response_factory;

        }

        public function handleException($e, $in_routing_flow = false, ?Request $request = null)
        {

            $this->logException($e);

            $response = $this->convertToResponse($e, $request ?? $this->resolveRequestFromContainer());

            if ($in_routing_flow) {

                return $response;

            }

            (new ResponseEmitter())->emit($response);

            // Shuts down the script if not running unit tests.
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
         * @param  Throwable  $e
         * @param  Request  $request
         *
         * @return HttpException
         */
        protected function toHttpException(Throwable $e, Request $request) : HttpException
        {

            $e = new HttpException(500, $this->fallback_error_message);

            return $e;

        }

        private function convertToResponse(Throwable $e, Request $request) : Response
        {

            if (method_exists($e, 'render')) {

                return $this->renderableException($e, $request);

            }

            if ($e instanceof ValidationException) {

                return $this->renderValidationException($e, $request);

            }

            if ( ! $e instanceof HttpException) {

                $e = $this->toHttpException($e, $request);

            }

            return $this->renderHttpException($e, $request);


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

        private function renderValidationException(ValidationException $e, Request $request)
        {

            if ($request->isExpectingJson()) {

                return $this->response->json([

                    'message' => $e->jsonMessage(),
                    'errors' => $e->errorsAsArray(),

                ], $e->getStatusCode());

            }

            $response = $this->response->redirect()->previous($request);

            if ( ! $response->hasSession()) {

                return $response;

            }

            return $response->withErrors($e->messages(), $e->namedBag())
                            ->withInput(Arr::except($request->input(), $this->dont_flash));

        }

        private function renderableException(Throwable $e, Request $request) : Response
        {

            /** @var Response $response */
            $response = $this->container->call([$e, 'render'], ['request' => $request]);

            // User did not provide a valid response from the callback.
            if ( ! $response instanceof Response) {

                return $this->renderHttpException(
                    new HttpException(500, $this->fallback_error_message),
                    $request
                );

            }

            return $response;
        }

    }
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
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Http\Response;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Traits\HandlesExceptions;

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
         * @var HttpResponseFactory
         */
        protected $response;

        public function __construct(ContainerAdapter $container, LoggerInterface $logger, HttpResponseFactory $response_factory, bool $is_ajax)
        {

            $this->is_ajax = $is_ajax;
            $this->container = $container;
            $this->logger = $logger;
            $this->response = $response_factory;
        }

        public function handleException($exception, $in_routing_flow = false)
        {

            $this->logException($exception);

            $response = $this->createResponseObject($exception);

            if ($in_routing_flow) {

                return $response;

            }

            (new ResponseEmitter())->emit($response);

            // Shut down the script
            UnrecoverableExceptionHandled::dispatch();

        }

        public function transformToResponse(Throwable $exception) : ?Response
        {

            return $this->handleException($exception, true);

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
         * @return Response
         */
        protected function defaultResponse() : Response
        {

            if ($this->is_ajax) {

                return $this->response->json('Internal Server Error', 500);

            }

            return $this->response->html('Internal Server Error', 500);

        }

        private function createResponseObject(Throwable $e) : Response
        {

            if (method_exists($e, 'render')) {

                /** @var Response $response */
                $response = $this->container->call([$e, 'render']);

                if ( ! $response instanceof Response) {

                    return $this->defaultResponse();

                }

                return $response;

            }

            if ($e instanceof HttpException) {

                return $this->renderHttpException($e);

            }

            return $this->defaultResponse();



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

        private function renderHttpException(HttpException $e) : Response
        {

            if ( $this->is_ajax ) {

                return $this->response->json( $e->getMessageForHumans() ?? '' , (int) $e->getStatusCode() );

            }

            return $this->response->error($e);

        }


    }
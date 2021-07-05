<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Throwable;
    use WPMvc\Contracts\ErrorHandlerInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Session\Session;

    class ErrorHandlerMiddleware extends Middleware
    {

        /**
         * @var ErrorHandlerInterface
         */
        private $error_handler;

        public function __construct(ErrorHandlerInterface $error_handler)
        {

            $this->error_handler = $error_handler;

        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            try {

                return $next($request);

            }

            catch (Throwable $e) {

                return $this->error_handler->transformToResponse($e, $request);

            }

        }

    }
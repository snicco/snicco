<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Throwable;
    use BetterWP\Contracts\ErrorHandlerInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Session\Session;

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
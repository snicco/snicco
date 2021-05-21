<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    class ErrorHandlerMiddleware extends Middleware
    {
        /**
         * @var ErrorHandlerInterface
         */
        private $error_handler;

        public function __construct( ErrorHandlerInterface $error_handler )
        {

            $this->error_handler = $error_handler;
        }

        public function handle(Request $request, Delegate $next)
        {

            try {

                return $next($request);

            }

            catch ( Throwable $e ) {

                return $this->error_handler->transformToResponse($e);

            }

        }

    }
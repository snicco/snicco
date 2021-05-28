<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;

    class CsrfMiddleware extends Middleware
    {

        /**
         * @var Guard
         */
        private $guard;
        /**
         * @var bool
         */
        private $persist_tokens;
        /**
         * @var ResponseFactory
         */
        private $response_factory;
        /**
         * @var ErrorHandlerInterface
         */
        private $error_handler;

        public function __construct(ErrorHandlerInterface $error_handler, Guard $guard, string $persistent_mode = 'rotate')
        {
            $this->error_handler = $error_handler;
            $this->guard = $guard;
            $this->persist_tokens = $persistent_mode;

            if (strtolower($this->persist_tokens) === 'persist') {

                $this->guard->setPersistentTokenMode(true);

            }

        }

        public function _handle(Request $request, Delegate $next)
        {

            try {

                $response = $this->guard->process($request, $next);

            }
            catch (InvalidCsrfTokenException $e) {

                // Slim does not run the enforce storage limit method when validation failed.
                // Slim assumes that all csrf tokens are stored in one $_SESSION array but for use
                // one active session only stores one token for the current user so that
                // when validation fails we want to clear everything out.
                $request->getSession()->forget('csrf');


                return $this->response_factory->error($e->setRequest($request));

            }

            return $response;

        }

        public function handle(Request $request, Delegate $next)
        {

            $response = $this->guard->process($request, $next);

            // Slim returns a 400 status code for failed validation
            if ($response->getStatusCode() === 400) {

                // Slim does not run the enforce storage limit method when validation failed.
                // Slim assumes that all csrf tokens are stored in one $_SESSION array but for use
                // one active session only stores one token for the current user so that
                // when validation fails we want to clear everything out.

                $request->getSession()->forget('csrf');

                // Let the error handler create a response object.
                // Dont interrupt the routing flow here so we can save the session correctly.
                return $this->error_handler->transformToResponse((new InvalidCsrfTokenException())->setRequest($request));


            }

            return $response;

        }


    }
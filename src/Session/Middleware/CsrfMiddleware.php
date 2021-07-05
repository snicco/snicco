<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Slim\Csrf\Guard;
    use WPMvc\Contracts\ErrorHandlerInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\Session\Exceptions\InvalidCsrfTokenException;

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


        public function __construct(Guard $guard, string $persistent_mode = 'rotate')
        {
            $this->guard = $guard;
            $this->persist_tokens = $persistent_mode;

            if (strtolower($this->persist_tokens) === 'persist') {

                $this->guard->setPersistentTokenMode(true);

            }

        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            try {

                return $this->guard->process($request, $next);

            }
            catch (InvalidCsrfTokenException $e) {

                // Slim does not run the enforce storage limit method when validation failed.
                // Slim assumes that all csrf tokens are stored in one $_SESSION array but for use
                // one active session only stores one token for the current user so that
                // when validation fails we want to clear everything out.
                $request->session()->forget('csrf');

                // Let error handling process the exception.
                throw $e;

            }


        }


    }
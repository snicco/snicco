<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
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

        public function __construct( ResponseFactory $response_factory, Guard $guard, string $persistent_mode = 'rotate' )
        {

            $this->response_factory = $response_factory;
            $this->guard = $guard;
            $this->persist_tokens = $persistent_mode;

            if (  strtolower($this->persist_tokens) === 'persist' ) {

                $this->guard->setPersistentTokenMode(true);

            }

        }

        public function handle(Request $request, Delegate $next)
        {

            try {

                $response = $this->guard->process($request, $next);

            } catch ( InvalidCsrfTokenException $e ) {

                // Slim does not run the enforce storage limit method when validation failed.
                // Slim assumes that all csrf tokens are stored in one $_SESSION array but for use
                // one active session only stores one token for the current user so that
                // when validation fails we want to clear everything out.
                $request->getSession()->forget('csrf');

                return $this->response_factory->error($e);

            }


            return $response;

        }


    }
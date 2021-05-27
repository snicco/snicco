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

        public function __construct( ResponseFactory $response_factory, Guard $guard, bool $persistent_token = false )
        {

            $this->response_factory = $response_factory;
            $this->guard = $guard;
            $this->persist_tokens = $persistent_token;

            if (  $this->persist_tokens ) {

                $this->guard->setPersistentTokenMode(true);

            }

        }

        public function handle(Request $request, Delegate $next)
        {

            try {

                $response = $this->guard->process($request, $next);

            } catch ( InvalidCsrfTokenException $e ) {

                // Slim does not run the enforce storage limit method when validation failed.
                // When validation fails we clear everything out.
                $request->getSession()->forget('csrf');

                return $this->response_factory->error($e);

            }


            return $response;

        }


    }
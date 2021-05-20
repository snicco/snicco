<?php


    declare(strict_types = 1);


    namespace Tests\stubs\GlobalMiddleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    class UnauthorizedMiddleware extends Middleware
    {

        /**
         * @var ResponseFactory
         */
        private $response;
        /**
         * @var bool
         */
        private $unauthorized;

        public function __construct(ResponseFactory $response, bool $unauthorized = true )
        {
            $this->response = $response;
            $this->unauthorized = $unauthorized;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( ! $this->unauthorized ) {

                return $next($request);

            }

            return $this->response->html('Unauthorized');

        }

    }
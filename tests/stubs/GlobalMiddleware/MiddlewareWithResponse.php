<?php


    declare(strict_types = 1);


    namespace Tests\stubs\GlobalMiddleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    class MiddlewareWithResponse extends Middleware
    {

        /**
         * @var \WPEmerge\Http\HttpResponseFactory
         */
        private $response;
        /**
         * @var bool
         */
        private $unauthorized;

        public function __construct(HttpResponseFactory $response, bool $unauthorized = true )
        {
            $this->response = $response;
            $this->unauthorized = $unauthorized;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( ! $this->unauthorized ) {

                return $next($request);

            }

            return $this->response->html('Unauthorized')->withStatus(403);

        }

    }
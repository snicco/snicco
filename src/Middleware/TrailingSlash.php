<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Support\Str;
    use WPEmerge\Support\Url;

    class TrailingSlash extends Middleware
    {

        /**
         * @var ResponseFactory
         */
        private $response_factory;
        /**
         * @var bool
         */
        private $trailing_slash;

        public function __construct(ResponseFactory $response_factory, bool $trailing_slash = true)
        {
            $this->response_factory = $response_factory;
            $this->trailing_slash = $trailing_slash;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( ! $request->isWpFrontEnd() ) {
                return $next($request);
            }

            $path = $request->path();

            $accept_request = $this->trailing_slash
                ? Str::endsWith($path, '/')
                : Str::doesNotEndWith($path, '/');

            if ( $accept_request || $path === '/') {

                return $next($request);

            }

            $redirect_to = $this->trailing_slash
                ? Url::addTrailing($path)
                : Url::removeTrailing($path);

            return $this->response_factory->permanentRedirectTo($redirect_to);

        }

    }
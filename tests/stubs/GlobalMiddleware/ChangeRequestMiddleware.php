<?php


    declare(strict_types = 1);


    namespace Tests\stubs\GlobalMiddleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    class ChangeRequestMiddleware extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            $request = $request->withAttribute('foo', 'bar');

            return $next($request);

        }

    }
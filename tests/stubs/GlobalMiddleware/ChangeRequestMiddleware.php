<?php


    declare(strict_types = 1);


    namespace Tests\stubs\GlobalMiddleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    class ChangeRequestMiddleware extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            $request = $request->withAttribute('foo', 'bar');

            return $next($request);

        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Support\VariableBag;

    class ShareCookies extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {
            return $next(
                $request->withAttribute(
                    'cookies',
                    new VariableBag( $this->parseCookiesFromRequest($request))
                )
            );
        }

        private function parseCookiesFromRequest (Request $request) : array
        {

            $cookies = $_COOKIE;

            return $cookies;

        }

    }
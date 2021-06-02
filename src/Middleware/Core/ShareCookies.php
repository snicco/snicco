<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    class ShareCookies extends Middleware
    {

        /**
         * @var Cookies
         */
        private $cookies;

        public function __construct(Cookies $cookies)
        {

            $this->cookies = $cookies;
        }

        public function handle(Request $request, Delegate $next)
        {

            $response = $next($this->addCookiesToRequest($request));

            return $this->addCookiesToResponse($response);


        }

        private function addCookiesToRequest ( Request $request) {

            return $request->withCookies($this->parseCookiesFromRequest($request));

        }

        private function addCookiesToResponse(ResponseInterface $response) : ResponseInterface
        {

            if ( ( $headers = $this->cookies->toHeaders() ) === [] ) {

                return $response;

            }

            return $response->withHeader('Set-Cookie', $headers);

        }

        private function parseCookiesFromRequest (Request $request) : array
        {

            return Cookies::parseHeader($request->getHeader('Cookie'));

        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class ShareCookies extends Middleware
    {

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            $response = $next($request);

            return $this->addCookiesToResponse($response);


        }

        private function addCookiesToResponse(Response $response) : ResponseInterface
        {

            if ( ( $headers = $response->cookies()->toHeaders() ) === [] ) {

                return $response;

            }

            foreach ($headers as $header) {

                $response = $response->withAddedHeader('Set-Cookie', $header);

            }

            return $response;

        }


    }
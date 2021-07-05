<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Cookies;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

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
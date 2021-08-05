<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\ExceptionHandling\Exceptions\InvalidResponseException;
    use Snicco\ExceptionHandling\Exceptions\NotFoundException;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Responses\InvalidResponse;
    use Snicco\Http\Responses\NullResponse;
    use Snicco\Http\Responses\WpQueryFilteredResponse;

    class EvaluateResponseMiddleware extends Middleware
    {

        /**
         * @var bool
         */
        private $must_match_current_request;

        public function __construct(bool $must_match_current_request = false)
        {

            $this->must_match_current_request = $must_match_current_request;
        }

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            $response = $next($request);

            return $this->passOnIfValid($response, $request);

        }

        private function passOnIfValid(ResponseInterface $response, Request $request) : ResponseInterface
        {

            // We had a route action return something, but it was not transformable to a Psr7 Response.
            if ($response instanceof InvalidResponse) {

                throw new InvalidResponseException(
                    "Invalid response returned by the route for path [{$request->fullPath()}]."
                );

            }

            // A route matched but the developer decided that he just wants to alter the main
            // wp query and let the WP template engine figure out what to load.
            if ($response instanceof WpQueryFilteredResponse) {

                return $response;

            }

            // Valid response
            if ( ! $response instanceof NullResponse) {

                return $response;

            }

            // We have a NullResponse, which means no route matched.
            // But we want WordPress to handle it from here.
            if ( ! $this->must_match_current_request) {

                return $response;

            }

            throw new NotFoundException("404 for request path [{$request->fullPath()}]");

        }


    }
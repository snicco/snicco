<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware\Core;

    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Contracts\Middleware;
    use Psr\Http\Message\ResponseInterface;
    use Snicco\Http\Responses\NullResponse;
    use Snicco\Http\Responses\InvalidResponse;
    use Snicco\ExceptionHandling\Exceptions\NotFoundException;
    use Snicco\ExceptionHandling\Exceptions\InvalidResponseException;

    class EvaluateResponseMiddleware extends Middleware
    {
    
        private bool $must_match_current_request;
    
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
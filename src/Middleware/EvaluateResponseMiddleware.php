<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidResponseException;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\InvalidResponse;
    use WPEmerge\Http\NullResponse;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

    class EvaluateResponseMiddleware extends Middleware
    {

        /**
         * @var bool
         */
        private $must_match_web_routes;

        public function __construct(bool $must_match_web_routes = false)
        {
            $this->must_match_web_routes = $must_match_web_routes;
        }

        public function handle(Request $request, Delegate $next)
        {
            $response = $next($request);

            $this->passOnIfValid($response);

        }


        private function passOnIfValid(ResponseInterface $response) : ResponseInterface
        {
            // We had a route action return something but it was not transformable to a Psr7 Response.
            if ($response instanceof InvalidResponse) {

                throw new InvalidResponseException(
                    'The response returned by the route action is not valid.'
                );

            }

            // Valid response
            if ( ! $response  instanceof NullResponse ) {

                return $response;

            }

            // We have a NullResponse, which means no route matched.
            // But we want WordPress to handle it from here.
            if ( ! $this->must_match_web_routes ) {

                return $response;

            }

            throw new HttpException(404);

        }

    }
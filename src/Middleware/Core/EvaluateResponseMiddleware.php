<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WP_Query;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidResponseException;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Responses\InvalidResponse;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\WpQueryFilteredResponse;

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

        public function handle(Request $request, Delegate $next)
        {

            $response = $next($request);

            return $this->passOnIfValid($response, $request);

        }

        private function passOnIfValid(ResponseInterface $response, Request $request) : ResponseInterface
        {

            // We had a route action return something but it was not transformable to a Psr7 Response.
            if ($response instanceof InvalidResponse) {

                throw new InvalidResponseException(
                    'The response returned by the route action is not valid.'
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

            throw new NotFoundException('This page could not be found');

        }


    }
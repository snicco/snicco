<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\ResponsableInterface;

    trait ConvertsToResponse
    {

        /** @todo handle the case where a route matched but invalid response was returned */
        private function prepareResponse( $response ) : Response {

            if ( $response instanceof ResponseInterface ) {

                return new Response($response);

            }

            if ( is_string( $response ) ) {

                return $this->response_factory->html($response);

            }

            if ( is_array( $response ) ) {

                return $this->response_factory->json($response);

            }

            if ( $response instanceof ResponsableInterface ) {

                return $this->response_factory->json($response);

            }

            return $this->response_factory->null();

            // /**
            //  * @todo Decide how this should be handled in production.
            //  *  500, 404 ?
            //  */
            // if ( $this->is_takeover_mode ) {
            //
            // 	throw new InvalidResponseException(
            // 		'The response by the route action is not valid.'
            // 	);
            //
            // }


        }

    }
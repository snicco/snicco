<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\ResponsableInterface;

    trait ConvertsToResponse
    {

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


        }

    }
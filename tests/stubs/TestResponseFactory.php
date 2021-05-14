<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use Tests\CreatePsr17Factories;
    use WPEmerge\Contracts\ResponseFactory;
	use WPEmerge\Http\Response;

	/** @delete this class and use the real implementation.  */
	class TestResponseFactory implements ResponseFactory {

	    use CreatePsr17Factories;

		public function view( string $view, array $data = [], $status = 200, array $headers = [] ) : Response {

			$additional_data = ':';

			foreach ($data as $key => $value ) {

				$additional_data .= $key . '=>' . $value;

			}

			$content = $view . $additional_data;

			$psr =$this->psrResponseFactory()->createResponse($status, 'OK', $headers, $content);

			return (new Response($psr))->html();


		}

        public function prepareResponse($response) : Response
        {
            // TODO: Implement prepareResponse() method.
        }

    }
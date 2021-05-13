<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use Tests\CreateResponseFactory;
    use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Http\Response;

	class TestResponseFactory implements ResponseFactoryInterface {

	    use CreateResponseFactory;

		public function view( string $view, array $data = [], $status = 200, array $headers = [] ) : Response {

			$additional_data = ':';

			foreach ($data as $key => $value ) {

				$additional_data .= $key . '=>' . $value;

			}

			$content = $view . $additional_data;

			$psr =$this->createFactory()->createResponse($status, 'OK', $headers, $content);

			return (new Response($psr))->html();


		}

	}
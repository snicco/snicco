<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Http\Response;

	class TestResponseFactory implements ResponseFactoryInterface {

		public function view( string $view, array $data = [], $status = 200, array $headers = [] ) : ResponseInterface {

			$additional_data = ':';

			foreach ($data as $key => $value ) {

				$additional_data .= $key . '=>' . $value;

			}

			$content = $view . $additional_data;

			return  ( new Response($content, $status, $headers))->setType('text/html');

		}

	}
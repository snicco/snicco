<?php


	namespace Tests\stubs;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Responses\RedirectResponse;

	class TestResponseService implements ResponseServiceInterface {

		/** @var \Tests\stubs\TestResponse */
		public $body_response;

		/** @var \Tests\stubs\TestResponse */
		public $header_response;


		public function sendBody( ResponseInterface $response, $chunk_size = 4096 ) {

			$this->body_response = $response;

		}

		public function sendHeaders( ResponseInterface $response ) :void {

			$this->header_response = $response;

		}

		public function respond( ResponseInterface $response ) : void {
			// Nothing
		}

		public function output( string $output ) {
			// Nothing
		}

		public function json( $data ) {
			// Nothing
		}

		public function redirect( RequestInterface $request = null ) {
			// Nothing
		}

	}
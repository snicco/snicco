<?php


	namespace Tests\stubs;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;

	class TestResponseService implements ResponseServiceInterface {

		/** @var \Tests\stubs\TestResponse */
		public $body_response;

		/** @var \Tests\stubs\TestResponse */
		public $header_response;


		public function sendBody( ResponseInterface $response, $chunk_size = 4096 ) {

			$this->body_response = $response;

		}

		public function sendHeaders( ResponseInterface $response ) {

			$this->header_response = $response;

		}

	}
<?php


	namespace WPEmerge\Contracts;


	use Psr\Http\Message\ResponseInterface;

	interface ResponseServiceInterface {

		/**
		 * Send a request's body to the client.
		 *
		 * @param  ResponseInterface $response
		 * @param  integer           $chunk_size
		 * @return void
		 */
		public function sendBody( ResponseInterface $response, $chunk_size = 4096 );

		/**
		 * Send a request's headers to the client.
		 *
		 * @param  ResponseInterface $response
		 * @return void
		 */
		public function sendHeaders( ResponseInterface $response ) :void ;

	}
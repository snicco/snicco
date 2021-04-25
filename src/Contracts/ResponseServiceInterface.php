<?php


	namespace WPEmerge\Contracts;


	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Responses\RedirectResponse;

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

		/**
		 * Send a complete response to the client in one shot.
		 *
		 * @param  ResponseInterface $response
		 * @return void
		 */
		public function respond ( ResponseInterface $response) :void;

		/**
		 * Get a cloned response with the passed string as the body.
		 *
		 * @param  string  $output
		 *
		 * @return ResponseInterface
		 */
		public function output( string $output );

		/**
		 * Get a cloned response, json encoding the passed data as the body.
		 *
		 * @param  mixed  $data
		 *
		 * @return ResponseInterface
		 */
		public function json( $data );

		/**
		 * Get a cloned response, with location and status headers.
		 *
		 * @param  RequestInterface|null  $request
		 *
		 * @return RedirectResponse
		 */
		public function redirect( RequestInterface $request = null );



	}
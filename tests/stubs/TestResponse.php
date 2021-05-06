<?php


	namespace Tests\stubs;

	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\StreamInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Http\Response;

	class TestResponse implements ResponseInterface {


		/**
		 * @var \WPEmerge\Contracts\RequestInterface
		 */
		private $request;

		public function __construct( RequestInterface $request) {

			$this->request = $request;

		}

		public function getProtocolVersion() {
			// TODO: Implement getProtocolVersion() method.
		}

		public function withProtocolVersion( $version ) {
			// TODO: Implement withProtocolVersion() method.
		}

		public function getHeaders() {
			// TODO: Implement getHeaders() method.
		}

		public function hasHeader( $name ) {
			// TODO: Implement hasHeader() method.
		}

		public function getHeader( $name ) {
			// TODO: Implement getHeader() method.
		}

		public function getHeaderLine( $name ) {
			// TODO: Implement getHeaderLine() method.
		}

		public function withHeader( $name, $value ) {
			// TODO: Implement withHeader() method.
		}

		public function withAddedHeader( $name, $value ) {
			// TODO: Implement withAddedHeader() method.
		}

		public function withoutHeader( $name ) {
			// TODO: Implement withoutHeader() method.
		}

		public function getBody() {
			// TODO: Implement getBody() method.
		}

		public function withBody( StreamInterface $body ) {
			// TODO: Implement withBody() method.
		}

		public function getStatusCode() {
			// TODO: Implement getStatusCode() method.
		}

		public function withStatus( $code, $reasonPhrase = '' ) {
			// TODO: Implement withStatus() method.
		}

		public function getReasonPhrase() {
			// TODO: Implement getReasonPhrase() method.
		}

		public function body () {

			return $this->request->body;

		}

	}
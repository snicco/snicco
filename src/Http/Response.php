<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use ArrayObject;
	use Illuminate\Contracts\Support\Arrayable;
	use Illuminate\Contracts\Support\Jsonable;
	use JsonSerializable;
	use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
	use Symfony\Component\HttpFoundation\ResponseHeaderBag;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;

	class Response extends SymfonyResponse implements ResponseInterface {


		public function __construct( $content = '', int $status = 200, array $headers = [] ) {

			$this->headers = new ResponseHeaderBag( $headers );
			$this->setBody( $content );
			$this->setStatusCode( $status );
			$this->setProtocolVersion( '1.0' );

		}

		public function setType( string $type ) : ResponseInterface {

			$this->headers->set( 'Content-Type', $type );

			return $this;

		}

		public function setBody(  $content ) : ResponseInterface {

			if ( $this->shouldBeJson( $content ) ) {

				$this->content = $this->toJson( $content );
				$this->setType( 'application/json' );

				return $this;

			}

			$this->content = $content ?? '';

			return $this;

		}

		public function body() : string {

			return $this->content;

		}

		public function status() : int {

			return $this->getStatusCode();

		}

		public function header( $name ) : ?string {

			return $this->headers->get( $name );

		}

		/**
		 * Determine if the given content should be turned into JSON.
		 *
		 * @param  mixed  $content
		 *
		 * @return bool
		 */
		private function shouldBeJson( $content ) : bool {

			return $content instanceof Arrayable
			       || $content instanceof Jsonable
			       || $content instanceof ArrayObject
			       || $content instanceof JsonSerializable
			       || is_array( $content );

		}

		/**
		 * Morph the given content into JSON.
		 *
		 * @param  mixed  $content
		 *
		 * @return string
		 */
		private function toJson( $content ) : string {

			if ( $content instanceof Jsonable ) {
				return $content->toJson();
			} elseif ( $content instanceof Arrayable ) {
				return json_encode( $content->toArray() );
			}

			return json_encode( $content );
		}

		public function prepareForSending( RequestInterface $request ) : ResponseInterface {

			return parent::prepare( $request );

		}

		public function sendHeaders() {

			parent::sendHeaders();

		}

		public function sendBody() {

			parent::sendContent();

		}

	}
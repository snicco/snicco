<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;

	class RedirectResponse extends Response {


		/**
		 * @var \WPEmerge\Contracts\RequestInterface
		 */
		private $request;

		public function __construct( RequestInterface $request, int $status = 302, string $url = null ) {

			parent::__construct( '', $status );

			if ( $url ) {

				$this->to( $url );


			}

			$this->request = $request;


		}

		public function to( string $url ) : ResponseInterface {

			$this->headers->set( 'Location', $url );

			return $this;

		}

		/**
		 * Get a response redirecting back to the referrer or a fallback.
		 *
		 * @param  string|null  $fallback
		 *
		 * @return ResponseInterface
		 */
		public function back( string $fallback = null ) : ResponseInterface {

			$url = $this->request->headers( 'Referer' );

			if ( empty( $url ) ) {

				$url = $fallback;

			}

			if ( empty( $url ) ) {

				$url = $this->request->fullUrl();

			}


			return $this->to( $url );
		}


	}

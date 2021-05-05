<?php


	namespace WPEmerge\Responses;

	use GuzzleHttp\Psr7\Response as Psr7Response;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RequestInterface;


	class RedirectResponse extends Psr7Response {

		/**
		 * Current request.
		 *
		 * @var RequestInterface
		 */
		private $request;



		public function __construct( RequestInterface $request ) {

			parent::__construct();
			$this->request = $request;
		}

		/**
		 * Get a response redirecting to a specific url.
		 *
		 * @param  string  $url
		 * @param  integer  $status
		 *
		 * @return ResponseInterface
		 */
		public function to( $url, $status = 302 ) : ResponseInterface {

			return $this
				->withHeader( 'Location', $url )
				->withStatus( $status );

		}

		/**
		 * Get a response redirecting back to the referrer or a fallback.
		 *
		 * @param  string  $fallback
		 * @param  integer  $status
		 *
		 * @return ResponseInterface
		 */
		public function back( string $fallback = null, int $status = 302 ) : ResponseInterface {

			$url = $this->request->getHeaderLine( 'Referer' );

			if ( empty( $url ) ) {
				$url = $fallback;
			}

			if ( empty( $url ) ) {
				$url = $this->request->getUrl();
			}

			return $this->to( $url, $status );
		}


	}

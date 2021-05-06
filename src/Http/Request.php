<?php


	namespace WPEmerge\Http;

	use GuzzleHttp\Psr7\ServerRequest;
	use GuzzleHttp\Psr7\Uri;
	use GuzzleHttp\Utils;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteCondition;
	use WPEmerge\Support\Url;
	use WPEmerge\Support\WPEmgereArr;

	class Request extends ServerRequest implements RequestInterface {


		/**
		 * @var \WPEmerge\Routing\Route|null The route that our request matched
		 */
		private $route = null;

		/** @var string The Type of request that's being handled. . */
		private $type;


		public static function fromGlobals() : Request {

			$request = parent::fromGlobals();
			$new     = new self(
				$request->getMethod(),
				$request->getUri(),
				$request->getHeaders(),
				$request->getBody(),
				$request->getProtocolVersion(),
				$request->getServerParams()
			);

			return $new
				->withCookieParams( $_COOKIE )
				->withQueryParams( $_GET )
				->withParsedBody( $_POST )
				->withUploadedFiles( static::normalizeFiles( $_FILES ) );
		}

		public function url() : string {

			return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/') . '/';


		}

		protected function getMethodOverride( $default ) : string {

			$valid_overrides = [ 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS' ];
			$override        = $default;

			$header_override = (string) $this->getHeaderLine( 'X-HTTP-METHOD-OVERRIDE' );
			if ( ! empty( $header_override ) ) {
				$override = strtoupper( $header_override );
			}

			$body_override = (string) $this->body( '_method', '' );
			if ( ! empty( $body_override ) ) {
				$override = strtoupper( $body_override );
			}

			if ( in_array( $override, $valid_overrides, true ) ) {
				return $override;
			}

			return $default;
		}


		public function getMethod() : string {

			$method = parent::getMethod();

			if ( $method === 'POST' ) {
				$method = $this->getMethodOverride( $method );
			}

			return $method;
		}


		public function isGet() : bool {

			return $this->getMethod() === 'GET';
		}


		public function isHead() : bool {

			return $this->getMethod() === 'HEAD';
		}


		public function isPost() : bool {

			return $this->getMethod() === 'POST';
		}


		public function isPut() : bool {

			return $this->getMethod() === 'PUT';
		}


		public function isPatch() : bool {

			return $this->getMethod() === 'PATCH';
		}


		public function isDelete() : bool {

			return $this->getMethod() === 'DELETE';
		}


		public function isOptions() : bool {

			return $this->getMethod() === 'OPTIONS';
		}


		public function isReadVerb() : bool {

			return in_array( $this->getMethod(), [ 'GET', 'HEAD', 'OPTIONS' ] );
		}

		public function isAjax() : bool {

			return strtolower( $this->getHeaderLine( 'X-Requested-With' ) ) === 'xmlhttprequest';
		}

		/** @todo improve this. See Laravel InteractsWithContentType */
		public function expectsJson() : bool {

			return $this->isAjax();

		}

		/**
		 * Get all values or a single one from an input type.
		 *
		 * @param  array  $source
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		protected function get( $source, $key = '', $default = null ) {

			if ( empty( $key ) ) {
				return $source;
			}

			return WPEmgereArr::get( $source, $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function attributes( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getAttributes(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function query( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getQueryParams(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function body( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getParsedBody(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function cookies( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getCookieParams(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function files( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getUploadedFiles(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function server( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getServerParams(), $key, $default );
		}

		/**
		 * {@inheritDoc}
		 * @see ::get()
		 */
		public function headers( $key = '', $default = null ) {

			return call_user_func( [ $this, 'get' ], $this->getHeaders(), $key, $default );
		}

		public function path() : string {

			return $this->getUri()->getPath();

		}

		/** @todo implement url retreival with query string. */
		public function fullUrl() : string {

			return $this->getUri()->__toString();

		}

		public function setRoute( RouteCondition $route ) {

			$this->route = $route;
		}

		public function setType( string $request_type ) {

			$this->type = $request_type;

		}

		public function type() : string {

			return $this->type;

		}

		public function route() : ?RouteCondition {

			return $this->route;

		}


	}

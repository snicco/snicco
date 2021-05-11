<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\InvalidCsrfTokenException;
	use WPEmerge\Session\Csrf;

	/**
	 * SessionStore current request data and clear old request data
	 */
	class CsrfProtection implements Middleware {

		/**
		 * CSRF service.
		 *
		 * @var Csrf
		 */
		protected $csrf = null;

		/**
		 * Constructor.
		 *
		 * @param  Csrf  $csrf
		 */
		public function __construct( $csrf ) {

			$this->csrf = $csrf;
		}

		/**
		 * Reject requests that fail nonce validation.
		 *
		 * @param  RequestInterface  $request
		 * @param  Closure  $next
		 * @param  mixed  $action
		 *
		 * @return ResponseInterface
		 * @throws InvalidCsrfTokenException
		 */
		public function handle( RequestInterface $request, Closure $next, $action = - 1 ) {

			if ( ! $request->isReadVerb() ) {
				$token = $this->csrf->getTokenFromRequest( $request );
				if ( ! $this->csrf->isValidToken( $token, $action ) ) {
					throw new InvalidCsrfTokenException();
				}
			}

			$this->csrf->generateToken( $action );

			return $next( $request );
		}

	}

<?php


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ResponseService;

	/**
	 * Redirect non-logged in users to a specific URL.
	 */
	class UserLoggedInMiddleware {

		/**
		 * Response service.
		 *
		 * @var ResponseService
		 */
		protected $response_service = null;

		/**
		 * Constructor.
		 *
		 *
		 * @param  ResponseService  $response_service
		 */
		public function __construct( ResponseService $response_service ) {

			$this->response_service = $response_service;
		}

		public function handle( RequestInterface $request, Closure $next, $url = '' ) {

			if ( is_user_logged_in() ) {
				return $next( $request );
			}

			if ( empty( $url ) ) {
				$url = wp_login_url( $request->getUrl() );
			}

			$url = apply_filters( 'wpemerge.middleware.user.logged_in.redirect_url', $url, $request );

			return $this->response_service->redirect( $request )->to( $url );
		}

	}

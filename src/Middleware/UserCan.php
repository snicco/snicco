<?php


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ResponseService;

	/**
	 * Redirect users who do not have a capability to a specific URL.
	 */
	class UserCan {

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


		public function handle( RequestInterface $request, Closure $next, $capability = '', $object_id = '0', $url = '' ) {

			$capability = apply_filters( 'wpemerge.middleware.user.can.capability', $capability, $request );
			$object_id  = apply_filters( 'wpemerge.middleware.user.can.object_id', (int) $object_id, $capability, $request );
			$args       = [ $capability ];

			if ( $object_id !== 0 ) {
				$args[] = $object_id;
			}

			if ( call_user_func_array( 'current_user_can', $args ) ) {
				return $next( $request );
			}

			if ( empty( $url ) ) {
				$url = home_url();
			}

			$url = apply_filters( 'wpemerge.middleware.user.can.redirect_url', $url, $request );

			return $this->response_service->redirect( $request )->to( $url );
		}

	}

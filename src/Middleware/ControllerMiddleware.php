<?php


	namespace WPEmerge\Middleware;


	/**
	 * Redirect users who do not have a capability to a specific URL.
	 */
	class ControllerMiddleware {

		/**
		 * Middleware.
		 *
		 * @var string[]
		 */
		protected $middleware = [];

		/**
		 * Methods the middleware applies to.
		 *
		 * @var string[]
		 */
		protected $whitelist = [];

		/**
		 * Methods the middleware does not apply to.
		 *
		 * @var string[]
		 */
		protected $blacklist = [];

		/**
		 * Constructor.
		 *
		 *
		 * @param  string|string[]  $middleware
		 */
		public function __construct( $middleware ) {

			$this->middleware = (array) $middleware;
		}

		/**
		 * Get middleware.
		 *
		 * @return string[]
		 */
		public function get() {

			return $this->middleware;
		}

		/**
		 * Set methods the middleware should apply to.
		 *
		 *
		 * @param  string|string[]  $methods
		 *
		 * @return static
		 */
		public function only( $methods ) {

			$this->whitelist = (array) $methods;

			return $this;
		}

		/**
		 * Set methods the middleware should not apply to.
		 *
		 *
		 * @param  string|string[]  $methods
		 *
		 * @return static
		 */
		public function except( $methods ) {

			$this->blacklist = (array) $methods;

			return $this;
		}

		/**
		 * Get whether the middleware applies to the specified method.
		 *
		 * @param  string  $method
		 *
		 * @return boolean
		 */
		public function appliesTo( $method ) {

			if ( in_array( $method, $this->blacklist, true ) ) {
				return false;
			}

			if ( empty( $this->whitelist ) ) {
				return true;
			}

			return in_array( $method, $this->whitelist, true );
		}

	}

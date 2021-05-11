<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use WPEmerge\Exceptions\Exception;
	use WPEmerge\Support\Arr;

	class ControllerMiddleware {

		/**
		 * Middleware.
		 *
		 * @var string
		 */
		private $middleware;

		/**
		 * Methods the middleware applies to.
		 *
		 * @var string[]
		 */
		private $whitelist = [];

		/**
		 * Methods the middleware does not apply to.
		 *
		 * @var string[]
		 */
		private $blacklist = [];

		/**
		 * @param string $middleware
		 */
		public function __construct( string $middleware ) {

			$this->middleware = $middleware;

		}

		/**
		 * Set methods the middleware should apply to.
		 *
		 * @param  string|string[]  $methods
		 *
		 */
		public function only( $methods ) : ControllerMiddleware {

			if ( ! empty($this->blacklist) ) {

				throw new Exception(
					'The only() method cant be combined with the except() method for one middleware'
				);

			}

			$this->whitelist = Arr::wrap($methods);

			return $this;

		}

		/**
		 * Set methods the middleware should not apply to.
		 *
		 *
		 * @param  string|string[]  $methods
		 *
		 */
		public function except( $methods ) : ControllerMiddleware {

			if ( ! empty($this->whitelist) ) {

				throw new Exception(
					'The only() method cant be combined with the except() method for one middleware'
				);

			}

			$this->blacklist = Arr::wrap($methods);

			return $this;

		}

		public function appliesTo ( string $method = null   ) : bool {

			if (  Arr::isValue($method, $this->blacklist) ) {

				return false;

			}

			if ( empty($this->whitelist ) ) {

				return true;

			}

			return Arr::isValue($method, $this->whitelist);


		}

		public function name() {

			return $this->middleware;

		}

	}
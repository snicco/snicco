<?php


	declare( strict_types = 1 );


	namespace WPMvc\Traits;

	use WPMvc\Http\Psr7\Request;

    trait HandlesExceptions {

		private $registered = false;

		private $request_resolver;

		public function register() {

			if ( $this->registered ) {

				return;

			}

			set_exception_handler( [ $this, 'handleException' ] );
			set_error_handler( [ $this, 'handleError' ] );


			$this->registered = true;

		}

		public function unregister() {

			if ( ! $this->registered ) {
				return;
			}

			restore_exception_handler();
			restore_error_handler();

			$this->registered = false;

		}

		public function handleError( $errno, $errstr, $errfile, $errline ) {

			if ( error_reporting() ) {

                throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );

			}


		}

		public function setRequestResolver ( \Closure $closure) {

		    $this->request_resolver = $closure;

        }

        public function resolveRequestFromContainer() :Request {

		    return call_user_func($this->request_resolver);

        }

	}
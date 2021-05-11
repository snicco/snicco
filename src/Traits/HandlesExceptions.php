<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;

	trait HandlesExceptions {

		private $registered = false;

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

				$this->handleException(
					new \ErrorException( $errstr, 0, $errno, $errfile, $errline ),
				);

			}


		}


	}
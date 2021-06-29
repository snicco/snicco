<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;

    class ShutdownHandler {


        /**
         * @var bool
         */
        private $running_unit_tests;

        public function __construct( bool $running_unit_tests = false )
        {
            $this->running_unit_tests = $running_unit_tests;
        }

        public function unrecoverableException () {

		   $this->terminate();

		}

		public function handle( ResponseSent $response_sent) {

            $request = $response_sent->request;

            if ( $request->isApiEndPoint() || $request->isWpAjax() ) {

                $this->terminate();

            }

		    if ( $response_sent->response instanceof RedirectResponse ) {

		        $this->terminate();

            }


        }

		private function terminate() {

            if ( $this->running_unit_tests ) {
                return;
            }

		    exit();

        }

	}
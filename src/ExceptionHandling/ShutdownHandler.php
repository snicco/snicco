<?php


	declare( strict_types = 1 );


	namespace Snicco\ExceptionHandling;

    use Snicco\Events\IncomingAjaxRequest;
    use Snicco\Events\ResponseSent;
    use Snicco\Http\Responses\RedirectResponse;

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
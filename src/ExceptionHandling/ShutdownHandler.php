<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;

    class ShutdownHandler {


        /**
         * @var string
         */
        private $request_type;
        /**
         * @var bool
         */
        private $running_unit_tests;

        public function __construct( string $request_type, bool $running_unit_tests = false )
        {
            $this->request_type = $request_type;
            $this->running_unit_tests = $running_unit_tests;
        }

        public function unrecoverableException () {

		   $this->terminate();

		}

		public function handle( ResponseSent $response_sent) {

            if ( $response_sent->request->isApiEndPoint()  ) {

                $this->terminate();

            }


		    if ( $this->request_type === IncomingAjaxRequest::class  ) {

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
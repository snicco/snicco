<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingGlobalRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;

    class ShutdownHandler {


        /**
         * @var string
         */
        private $request_type;

        public function __construct( string $request_type )
        {
            $this->request_type = $request_type;
        }

        public function unrecoverableException () {

		   $this->terminate();

		}

		public function handle( ResponseSent $response_sent) {

            if ( $response_sent->request->getType() === IncomingGlobalRequest::class ) {

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

		    exit();

        }

	}
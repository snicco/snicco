<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\ResponseSent;

    class ShutdownHandler {


		public function unrecoverableException () {

		  $this->terminate();

		}

		public function handle(ResponseSent $response_sent) {

		    $request = $response_sent->request;

		    if ( $request->getType() === IncomingAjaxRequest::class ) {

		        $this->terminate();

            }

		    if( $request->getAttribute('from_global_middleware') ) {

		        $this->terminate();

            }


        }



		private function terminate() {

		    exit();

        }

	}
<?php


	namespace WPEmerge;

	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\ResponseSent;

	class AjaxShutdownHandler {



		// Needed because normal Wordpress Ajax Handlers always terminate
		// with an output message of 0.
		public function shutdownWp( ResponseSent $response_sent_event ) {

			if ( $response_sent_event->request->type() === IncomingAjaxRequest::class ) {

				wp_die( '', '', [ 'response' => null ] );

			}

		}

	}
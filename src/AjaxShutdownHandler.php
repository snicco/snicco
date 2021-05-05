<?php


	namespace WPEmerge;

	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\BodySent;

	class AjaxShutdownHandler {



		// We need to terminate ajax scripts manually because if we dont Wordpress will
		// call wp_die() and always generate a 200 status code.
		public function shutdownWp( BodySent $response_sent_event ) {

			if ( $response_sent_event->request->type() === IncomingAjaxRequest::class ) {

				exit();

			}

		}

	}
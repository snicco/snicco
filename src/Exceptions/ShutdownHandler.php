<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Exceptions;

	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\IncomingAjaxRequest;

	class ShutdownHandler {


		public function exceptionHandled () {

			exit();

		}

		// We need to terminate ajax scripts manually because if we dont Wordpress will
		// call wp_die() and always generate a 200 status code.
		public function shutdownWp( BodySent $response_sent_event ) {

			if ( $response_sent_event->request->type() === IncomingAjaxRequest::class ) {

				exit();

			}

		}

	}
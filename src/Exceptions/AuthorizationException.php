<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Exceptions;

	use WPEmerge\Http\Request;
	use WPEmerge\Http\Response;

	class AuthorizationException extends Exception {

		public $redirect_to;

		public function render (Request $request ) {

			return new Response('You are not allowed to do this action:[' . $request->request->get('action') .']' , 419 );

		}

	}
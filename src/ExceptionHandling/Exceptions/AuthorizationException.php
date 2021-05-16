<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling\Exceptions;

    use WPEmerge\Http\Request;
	use WPEmerge\Http\Response;

	class AuthorizationException extends Exception {

		public $redirect_to;

		public function render (Request $request ) {

			return new Response('You are not allowed to do this perform this action.' , 419 );

		}

	}
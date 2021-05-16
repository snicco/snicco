<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling\Exceptions;

    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Request;

	class AuthorizationException extends Exception {

		public $redirect_to;

		public function render ( ResponseFactory $response, Request $request ) {

		    return $response
                ->html('You are not allowed to do this perform this action.')
                ->withStatus(419);

		}

	}
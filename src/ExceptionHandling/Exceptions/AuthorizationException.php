<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling\Exceptions;

    use Throwable;

	class AuthorizationException extends HttpException {


	    public function __construct( ?string $message = null, Throwable $previous = null, ?int $code = 0)
        {
            parent::__construct(403, $message, $previous, $code);
        }

        // public function render ( HttpResponseFactory $response, Request $request ) {
        //
		//     return $response
        //         ->html('You are not allowed to do this perform this action.')
        //         ->withStatus(419);
        //
		// }

	}
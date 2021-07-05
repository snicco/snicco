<?php


	declare( strict_types = 1 );


	namespace WPMvc\ExceptionHandling\Exceptions;

    use Throwable;

	class AuthorizationException extends HttpException {


	    public function __construct( ?string $message = 'You are not allowed to perform this action.', Throwable $previous = null, ?int $code = 0)
        {
            parent::__construct(403, $message, $previous, $code);

        }


	}
<?php


	declare( strict_types = 1 );


	namespace Snicco\ExceptionHandling\Exceptions;

    use Throwable;

    class AuthorizationException extends HttpException {

	    protected string $message_for_users = 'You are not allowed to perform this action.';

	    public function __construct( string $message_for_logging = 'Failed Authorization', Throwable $previous = null)
        {
            parent::__construct(403, $message_for_logging, $previous);
        }

	}
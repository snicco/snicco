<?php


    declare(strict_types = 1);


    namespace Snicco\ExceptionHandling\Exceptions;

    use Throwable;

    class InvalidSignatureException extends HttpException
    {

        protected string $message_for_users = 'You cant access this page.';

        public function __construct(string $message_for_logging = 'Failed signature check detected,', Throwable $previous = null)
        {
            parent::__construct(403, $message_for_logging, $previous);
        }

    }
<?php


    declare(strict_types = 1);


    namespace Snicco\Session\Exceptions;

    use Snicco\ExceptionHandling\Exceptions\HttpException;
    use Throwable;

    class InvalidCsrfTokenException extends HttpException
    {

        protected string $message_for_users = 'The link you followed expired.';

        public function __construct(string $message = 'Failed CSRF Check', Throwable $previous = null)
        {
            parent::__construct(419, $message, $previous);
        }

    }
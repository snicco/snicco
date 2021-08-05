<?php


    declare(strict_types = 1);


    namespace Snicco\ExceptionHandling\Exceptions;


    use Throwable;

    class ConfigurationException extends HttpException
    {

        protected string $message_for_users = 'Something went wrong on our end.';

        public function __construct( string $message_for_logging, Throwable $previous = null)
        {
            parent::__construct(500, $message_for_logging, $previous);
        }

    }

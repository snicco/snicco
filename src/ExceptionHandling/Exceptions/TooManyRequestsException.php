<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use Throwable;

    class TooManyRequestsException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'Too many requests. Slow down,', Throwable $previous = null, ?int $code = 0)
        {

            parent::__construct(429, $message_for_humans, $previous, $code);

        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Exceptions;

    use Throwable;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;

    class InvalidCsrfTokenException extends HttpException
    {

        public function __construct(int $status_code = 400, ?string $message_for_humans = 'The Link you follwed expired.', Throwable $previous = null, ?int $code = 0)
        {

            parent::__construct($status_code, $message_for_humans, $previous, $code);
        }

    }
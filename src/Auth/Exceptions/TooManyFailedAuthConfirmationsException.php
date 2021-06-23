<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Exceptions;

    use Throwable;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;

    class TooManyFailedAuthConfirmationsException extends HttpException
    {


        public function __construct(?string $message_for_humans = null, Throwable $previous = null, ?int $code = 0)
        {

            $this->status_code = 429;

            parent::__construct($this->status_code, $message_for_humans, $previous, $code);

        }

    }
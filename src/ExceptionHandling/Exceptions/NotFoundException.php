<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use Throwable;

    class NotFoundException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'We could not find what you are looking for.', Throwable $previous = null, ?int $code = 0)
        {

            $this->status_code = $message_for_humans;
            $this->message_for_humans = $message_for_humans;

        }

    }
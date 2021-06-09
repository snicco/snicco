<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use Throwable;

    class NotFoundException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'We could not find what you are looking for.', Throwable $previous = null, ?int $code = 0)
        {

            parent::__construct(404, $message_for_humans, $previous, $code);

        }

    }
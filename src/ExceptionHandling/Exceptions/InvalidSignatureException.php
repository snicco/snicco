<?php


    declare(strict_types = 1);


    namespace BetterWP\ExceptionHandling\Exceptions;

    use Throwable;
    use BetterWP\ExceptionHandling\Exceptions\HttpException;
    use BetterWP\Http\ResponseFactory;

    class InvalidSignatureException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'You cant access this page.', Throwable $previous = null, ?int $code = 0)
        {
            parent::__construct(403, $message_for_humans, $previous, $code);
        }




    }
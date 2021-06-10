<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Exceptions;


    use Throwable;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;

    class ValidationException extends HttpException
    {

        /**
         * @var array
         */
        private $errors;

        public function __construct(array $errors, ?string $message_for_humans = 'We could not process your request.', Throwable $previous = null, ?int $code = 0)
       {

           $this->errors = $errors;

           parent::__construct(400, $message_for_humans, $previous, $code);

       }

       public function getErrors() : array
       {
            return $this->errors;
       }

    }
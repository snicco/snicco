<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Exceptions;


    use Throwable;

    class ValidationException extends \RuntimeException
    {

        /**
         * @var array
         */
        private $errors;

        public function __construct(array $errors, $message = "", $code = 0, Throwable $previous = null)
       {

           $this->errors = $errors;
           parent::__construct($message, $code, $previous);
       }

       public function getErrors() : array
       {
            return $this->errors;
       }

    }
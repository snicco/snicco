<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Exceptions;

    use Throwable;

    class FailedAuthenticationException extends \Exception
    {

        /**
         * @var array
         */
        private $old_input;

        public function __construct($message, array $old_input, $code = 0, Throwable $previous = null)
        {

            parent::__construct($message, $code, $previous);
            $this->old_input = $old_input;
        }

        public function oldInput() : array
        {

            return $this->old_input;
        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use RuntimeException;
    use Throwable;

    class HttpException extends RuntimeException
    {

        /** @var int  */
        private $status_code;

        public function __construct(int $status_code, ?string $message = '', Throwable $previous = null, ?int $code = 0)
        {
            $this->status_code = $status_code;

            parent::__construct($message, $code, $previous);

        }

        public function getStatusCode() :int {

            return $this->status_code;

        }

    }
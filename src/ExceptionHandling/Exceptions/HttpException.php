<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use RuntimeException;
    use Throwable;

    class HttpException extends RuntimeException
    {

        /** @var int  */
        private $status_code;

        /**
         * @var string|null
         */
        private $message_for_humans;

        public function __construct(int $status_code, ?string $message = null, Throwable $previous = null, ?int $code = 0)
        {
            $this->status_code = $status_code;
            $this->message_for_humans = $message;

            parent::__construct('', $code, $previous);

        }

        public function getStatusCode() :string {

            return strval($this->status_code);

        }

        public function getMessageForHumans () :?string {

            return $this->message_for_humans;

        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling\Exceptions;

    use RuntimeException;
    use Throwable;
    use WPEmerge\Http\Psr7\Request;

    class HttpException extends RuntimeException
    {

        /** @var int  */
        protected $status_code;

        /**
         * @var Request
         */
        protected $request;

        public function __construct( int $status_code, ?string $message_for_humans = null, Throwable $previous = null, ?int $code = 0)
        {

            $this->status_code = $status_code;

            parent::__construct($message_for_humans, $code, $previous);

        }

        public function getStatusCode() : int
        {

            return $this->status_code;

        }

        public function causedBy(Request $request) : HttpException
        {
            $this->request = $request;
            return $this;
        }

        public function inAdminArea () :bool  {

            return $this->request->isWpAdmin();

        }

        /**
         * Returned string SHOULD NOT BE JSON ENCODED.
         *
         * @return string
         */
        public function jsonMessage () : string
        {
            return $this->getMessage();

        }

    }
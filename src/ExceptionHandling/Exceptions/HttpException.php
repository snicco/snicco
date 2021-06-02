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
         * @var string|null
         */
        protected $message_for_humans;

        /**
         * @var Request
         */
        protected $request;

        public function __construct(int $status_code, ?string $message_for_humans = null, Throwable $previous = null, ?int $code = 0)
        {
            $this->status_code = $status_code;
            $this->message_for_humans = $message_for_humans;

            parent::__construct('', $code, $previous);

        }

        public function getStatusCode() :string {

            return strval($this->status_code);

        }

        public function getMessageForHumans () :?string {

            return $this->message_for_humans;

        }

        public function setRequest(Request $request) {

            $this->request = $request;
            return $this;

        }

        public function isAjax() :bool {
            return $this->request && $this->request->isAjax();
        }

    }
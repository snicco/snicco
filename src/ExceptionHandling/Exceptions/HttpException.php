<?php


    declare(strict_types = 1);


    namespace Snicco\ExceptionHandling\Exceptions;

    use RuntimeException;
    use Snicco\Http\Psr7\Request;
    use Throwable;

    class HttpException extends RuntimeException
    {

        protected int     $status_code       = 500;
        protected Request $request;
        protected string  $message_for_users = 'Something went wrong.';
        protected ?string $json_message = null;

        public function __construct(int $status_code, string $message_for_logging, Throwable $previous = null)
        {

            $this->status_code = $status_code;
            parent::__construct($message_for_logging, 0, $previous);
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

        public function inAdminArea() : bool
        {

            return $this->request->isWpAdmin();
        }

        public function messageForUsers() : string
        {
            return $this->message_for_users;
        }

        public function withMessageForUsers(string $fallback_error_message) : HttpException
        {
            $this->message_for_users = $fallback_error_message;
            return $this;
        }

        // The message that should be displayed for json requests while in production mode.
        public function setJsonMessage(string $json_message) : HttpException
        {
            $this->json_message = $json_message;
            return $this;
        }

        public function getJsonMessage () {

            return $this->json_message ?? $this->messageForUsers();

        }


    }
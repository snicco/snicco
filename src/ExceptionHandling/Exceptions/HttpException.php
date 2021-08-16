<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;
use RuntimeException;

class HttpException extends RuntimeException
{
    
    protected int     $status_code;
    protected string  $message_for_users = 'Something went wrong.';
    protected ?string $json_message      = null;
    
    public function __construct(
        int $status_code,
        string $log_message,
        Throwable $previous = null
    ) {
        $this->status_code = $status_code;
        parent::__construct($log_message, 0, $previous);
    }
    
    public function httpStatusCode() :int
    {
        return $this->status_code;
    }
    
    public function messageForUsers() :string
    {
        return $this->message_for_users;
    }
    
    public function withMessageForUsers(string $message_for_users) :HttpException
    {
        $this->message_for_users = $message_for_users;
        return $this;
    }
    
    // The message that should be displayed for json requests while in production mode.
    public function withJsonMessageForUsers(string $json_message) :HttpException
    {
        $this->json_message = $json_message;
        return $this;
    }
    
    public function getJsonMessage() :string
    {
        return $this->json_message ?? $this->messageForUsers();
    }
    
    public function withStatusCode(int $http_status_code) :HttpException
    {
        $this->status_code = $http_status_code;
        return $this;
    }
    
}
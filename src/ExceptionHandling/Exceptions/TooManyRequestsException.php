<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;

class TooManyRequestsException extends HttpException
{
    
    protected string $message_for_users = 'Too many requests. Slow down.';
    
    public function __construct($log_message, Throwable $previous = null)
    {
        parent::__construct(429, $log_message, $previous);
    }
    
}
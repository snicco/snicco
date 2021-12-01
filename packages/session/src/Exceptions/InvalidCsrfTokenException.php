<?php

declare(strict_types=1);

namespace Snicco\Session\Exceptions;

use Throwable;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class InvalidCsrfTokenException extends HttpException
{
    
    protected string $message_for_users = 'The link you followed expired.';
    
    public function __construct(string $log_message = 'Failed CSRF Check', Throwable $previous = null)
    {
        parent::__construct(419, $log_message, $previous);
    }
    
}
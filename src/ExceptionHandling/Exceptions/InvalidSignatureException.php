<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;

class InvalidSignatureException extends HttpException
{
    
    protected string $message_for_users = 'You cant access this page.';
    
    public function __construct(string $log_message = 'Failed signature check', Throwable $previous = null)
    {
        parent::__construct(403, $log_message, $previous);
    }
    
}
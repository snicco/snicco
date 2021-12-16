<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling\Exceptions;

use Throwable;

class InvalidSignatureException extends HttpException
{
    
    protected $message_for_users = 'You cant access this page.';
    
    public function __construct(string $log_message = 'Failed signature check', Throwable $previous = null)
    {
        parent::__construct(403, $log_message, $previous);
    }
    
}
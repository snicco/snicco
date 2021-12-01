<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;
use RuntimeException;

class EncryptException extends RuntimeException
{
    
    public function __construct(string $message = 'Encryption failure', Throwable $previous = null)
    {
        parent::__construct(500, $message, $previous);
    }
    
}
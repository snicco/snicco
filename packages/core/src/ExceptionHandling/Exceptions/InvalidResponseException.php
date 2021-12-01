<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;

class InvalidResponseException extends HttpException
{
    
    public function __construct(string $message_for_logging = 'A route returned an invalid response', Throwable $previous = null)
    {
        parent::__construct(500, $message_for_logging, $previous);
    }
    
}
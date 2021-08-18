<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;

class ViewException extends HttpException
{
    
    public function __construct(string $log_message = 'Error rendering view', Throwable $previous = null)
    {
        parent::__construct(500, $log_message, $previous);
    }
    
}

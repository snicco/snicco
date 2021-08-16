<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling\Exceptions;

use Throwable;

class ViewNotFoundException extends ViewException
{
    
    public function __construct(string $log_message = 'View not found', Throwable $previous = null)
    {
        parent::__construct(
            $log_message,
            $previous
        );
    }
    
}

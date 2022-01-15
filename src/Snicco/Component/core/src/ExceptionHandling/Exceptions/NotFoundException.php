<?php

declare(strict_types=1);

namespace Snicco\Component\Core\ExceptionHandling\Exceptions;

use Throwable;

class NotFoundException extends HttpException
{
    
    protected $message_for_users = 'We could not find the resource you are looking for.';
    
    public function __construct($log_message, Throwable $previous = null)
    {
        parent::__construct(404, $log_message, $previous);
    }
    
}
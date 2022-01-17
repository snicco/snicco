<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Exceptions;

use Throwable;
use Snicco\Component\Core\ExceptionHandling\Exceptions\HttpException;

class InvalidCsrfTokenException extends HttpException
{
    
    /**
     * @var string
     */
    protected $message_for_users = 'The link you followed expired.';
    
    public function __construct(string $log_message = 'Failed CSRF Check', Throwable $previous = null)
    {
        parent::__construct(419, $log_message, $previous);
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Session\Exceptions;

use Throwable;
use LogicException;

/**
 * @api
 */
final class SessionIsLocked extends LogicException
{
    
    public function __construct($message = "The session has been modified and can not be changed any longer.", $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use Throwable;
use LogicException;

/**
 * @api
 */
final class SessionIsLocked extends LogicException
{
    
    public function __construct($message = "The session has been persisted and can not be changed any longer.", $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
    
}
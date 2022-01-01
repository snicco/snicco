<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Exceptions;

use Throwable;
use LogicException;

/**
 * @api
 */
final class BadRoute extends LogicException
{
    
    public static function fromPrevious(Throwable $previous) :BadRoute
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }
    
}
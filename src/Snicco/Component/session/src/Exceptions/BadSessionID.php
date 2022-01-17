<?php

declare(strict_types=1);

namespace Snicco\Session\Exceptions;

use InvalidArgumentException;

/**
 * @api
 * @todo refactor session exceptions
 */
final class BadSessionID extends InvalidArgumentException
{
    
    public static function forId(string $id, string $driver) :BadSessionID
    {
        return new self(
            "The session id [$id] does not exist in the [$driver] driver."
        );
    }
    
}
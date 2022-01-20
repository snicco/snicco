<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use InvalidArgumentException;

/**
 * @api
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
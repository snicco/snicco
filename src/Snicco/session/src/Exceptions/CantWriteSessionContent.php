<?php

declare(strict_types=1);

namespace Snicco\Session\Exceptions;

use Throwable;
use RuntimeException;

/**
 * @api
 */
final class CantWriteSessionContent extends RuntimeException
{
    
    public static function forId($id, string $driver_identifier, Throwable $previous = null) :CantWriteSessionContent
    {
        return new self(
            "Cant write session [$id] to the [$driver_identifier] driver.", 0, $previous
        );
    }
    
}
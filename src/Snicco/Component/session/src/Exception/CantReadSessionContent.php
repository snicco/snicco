<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use Throwable;
use RuntimeException;

/**
 * @api
 */
final class CantReadSessionContent extends RuntimeException
{
    
    public static function forID(string $id, string $driver, ?Throwable $previous = null) :CantReadSessionContent
    {
        return new CantReadSessionContent(
            "Cant read session content for session [$id] with driver [$driver].",
            $previous->getCode(),
            $previous
        );
    }
    
}
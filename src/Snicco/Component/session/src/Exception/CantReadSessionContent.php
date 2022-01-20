<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;

/**
 * @api
 */
final class CantReadSessionContent extends RuntimeException
{
    
    public static function forID(string $id, string $driver) :CantReadSessionContent
    {
        return new CantReadSessionContent(
            "Cant read session content for session [$id] with driver [$driver]."
        );
    }
    
}
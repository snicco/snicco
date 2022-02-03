<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

use function intval;

/**
 * @api
 */
final class CantReadSessionContent extends RuntimeException
{

    public static function forID(string $id, string $driver, ?Throwable $previous = null): CantReadSessionContent
    {
        return new CantReadSessionContent(
            "Cant read session content for session [$id] with driver [$driver].",
            intval($previous ? $previous->getCode() : 0),
            $previous
        );
    }

}
<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

final class CouldNotReadSessionContent extends RuntimeException
{
    public static function forID(string $id, string $driver, ?Throwable $previous = null): CouldNotReadSessionContent
    {
        return new CouldNotReadSessionContent(
            "Cant read session content for session [$id] with driver [$driver].",
            (int) ($previous ? $previous->getCode() : 0),
            $previous
        );
    }
}

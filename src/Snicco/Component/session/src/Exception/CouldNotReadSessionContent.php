<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

use function intval;

final class CouldNotReadSessionContent extends RuntimeException
{

    public static function forID(string $id, string $driver, ?Throwable $previous = null): CouldNotReadSessionContent
    {
        return new CouldNotReadSessionContent(
            "Cant read session content for session [$id] with driver [$driver].",
            intval($previous ? $previous->getCode() : 0),
            $previous
        );
    }

}
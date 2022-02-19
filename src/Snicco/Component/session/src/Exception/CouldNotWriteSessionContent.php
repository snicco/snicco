<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

final class CouldNotWriteSessionContent extends RuntimeException
{

    public static function forId(
        string $id,
        string $driver_identifier,
        Throwable $previous = null
    ): CouldNotWriteSessionContent {
        return new self(
            "Cant write session [$id] to the [$driver_identifier] driver.", 0, $previous
        );
    }

}
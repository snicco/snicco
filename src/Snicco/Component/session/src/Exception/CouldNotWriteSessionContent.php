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
        return new self(sprintf('Cant write session [%s] to the [%s] driver.', $id, $driver_identifier), 0, $previous);
    }
}

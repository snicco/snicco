<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

final class CouldNotDestroySession extends RuntimeException
{
    public static function forSelector(string $id, string $driver, Throwable $previous = null): CouldNotDestroySession
    {
        return new self(
            sprintf('Cant destroy session with selector [%s] with the [%s] driver.', $id, $driver),
            (int) (null !== $previous ? $previous->getCode() : 0),
            $previous
        );
    }
}

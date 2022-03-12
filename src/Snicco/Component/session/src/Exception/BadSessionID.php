<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use InvalidArgumentException;

final class BadSessionID extends InvalidArgumentException
{
    public static function forSelector(string $id, string $driver): BadSessionID
    {
        return new self(sprintf('The session selector [%s] does not exist in the [%s] driver.', $id, $driver));
    }
}

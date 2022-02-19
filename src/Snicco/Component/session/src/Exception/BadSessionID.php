<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use InvalidArgumentException;

final class BadSessionID extends InvalidArgumentException
{

    public static function forSelector(string $id, string $driver): BadSessionID
    {
        return new self(
            "The session selector [$id] does not exist in the [$driver] driver."
        );
    }

}
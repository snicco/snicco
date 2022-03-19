<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use InvalidArgumentException;

final class UnknownSessionSelector extends InvalidArgumentException
{
    public static function forSelector(string $id, string $driver): UnknownSessionSelector
    {
        return new self(sprintf('The session selector [%s] does not exist in the [%s] driver.', $id, $driver));
    }
}

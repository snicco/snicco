<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Exception;

use InvalidArgumentException;

final class CommandNotFound extends InvalidArgumentException
{
    public static function forClass(string $command_class): self
    {
        return new self(sprintf('The command [%s] does not exist in the command loader.', $command_class));
    }
}

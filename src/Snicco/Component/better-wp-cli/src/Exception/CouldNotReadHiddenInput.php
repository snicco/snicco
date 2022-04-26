<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Exception;

use RuntimeException;

final class CouldNotReadHiddenInput extends RuntimeException
{
    public static function becauseOSisWindows(): self
    {
        return new self('Could not read hidden input on Windows OS.');
    }

    public static function becauseSttyIsNotAvailable(): self
    {
        return new self('Could not read hidden input because stty is not available.');
    }
}

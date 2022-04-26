<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Logger;

use Snicco\Component\BetterWPCLI\Input\Input;
use Throwable;

interface Logger
{
    /**
     * Log an uncaught exception during command execution.
     */
    public function logError(Throwable $e, string $command_name, Input $input): void;

    /**
     * Log that a command exited with an exit-code between [1-255].
     *
     * @param positive-int $exit_code
     */
    public function logCommandFailure(int $exit_code, string $command_name, Input $input): void;
}

<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Logger;

use Snicco\Component\BetterWPCLI\Input\Input;
use Throwable;

use function error_log;
use function get_class;
use function sprintf;

/**
 * This is a simple logger implementation that just logs all errors using the
 * configuration of {@see error_log()}. This logger is suitable for distributed
 * code where you have no control over logging settings.
 */
final class StdErrLogger implements Logger
{
    private string $log_prefix;

    public function __construct(string $log_prefix)
    {
        $this->log_prefix = $log_prefix;
    }

    public function logError(Throwable $e, string $command_name, Input $input): void
    {
        $message = sprintf(
            "%s/better-wp-cli.CRITICAL: Error thrown while running command [%s].\n\tMessage: %s\n\tException: %s at %s on line %s.",
            $this->log_prefix,
            $command_name,
            $e->getMessage(),
            get_class($e),
            $e->getFile(),
            $e->getLine(),
        );

        error_log($message);
    }

    public function logCommandFailure(int $exit_code, string $command_name, Input $input): void
    {
        $message = sprintf(
            '%s/better-wp-cli.DEBUG: Command [%s] exited with code [%s].',
            $this->log_prefix,
            $command_name,
            $exit_code,
        );

        error_log($message);
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger;

use Psr\Log\AbstractLogger;
use Snicco\Component\MinimalLogger\Formatter\Formatter;
use Snicco\Component\MinimalLogger\Formatter\HumanReadableFormatter;

use function error_log;

use const PHP_EOL;

final class StdErrLogger extends AbstractLogger
{
    private Formatter $formatter;

    private string $channel;

    public function __construct(string $channel, Formatter $formatter = null)
    {
        $this->channel = $channel;
        $this->formatter = $formatter ?: new HumanReadableFormatter();
    }

    public function log($level, $message, array $context = []): void
    {
        $formatted = $this->formatter->format(
            (string) $level,
            $message,
            $context,
            "{$this->channel}."
        );

        error_log($formatted . PHP_EOL);
    }
}

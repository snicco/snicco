<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger;

use Psr\Log\AbstractLogger;
use RuntimeException;
use Snicco\Component\MinimalLogger\Formatter\Formatter;
use Snicco\Component\MinimalLogger\Formatter\HumanReadableFormatter;

use function date;
use function dirname;
use function fopen;
use function fwrite;
use function is_dir;
use function is_file;
use function mkdir;

use const PHP_EOL;

final class StreamLogger extends AbstractLogger
{
    private Formatter $formatter;

    private string $log_file;

    private string $channel;

    /**
     * @var resource|null
     */
    private $stream;

    public function __construct(string $log_file, string $channel, Formatter $formatter = null)
    {
        $this->formatter = $formatter ?: new HumanReadableFormatter();
        $this->log_file = $log_file;
        $this->channel = $channel;
    }

    public function log($level, $message, array $context = [])
    {
        $prefix = $this->linePrefix();

        $message = $this->formatter->format((string) $level, $message, $context, $prefix);

        $this->writeStream($message . PHP_EOL . PHP_EOL);
    }

    private function writeStream(string $message): void
    {
        if (! $this->stream) {
            if (! is_file($this->log_file)) {
                $log_dir = dirname($this->log_file);
                if (! is_dir($log_dir) && ! @mkdir($log_dir, 0777, true)) {
                    $this->throwWithMessage(
                        "Log file [{$this->log_file}] does not exists and the parent directory [{$log_dir}] could not be created.",
                        $message
                    );
                }
            }

            $stream = @fopen($this->log_file, 'a');

            if (false === $stream) {
                $this->throwWithMessage(
                    "Could not open stream for log file [{$this->log_file}].",
                    $message
                );
            }
            $this->stream = $stream;
        }

        // @codeCoverageIgnoreStart
        if (false === @fwrite($this->stream, $message)) {
            $this->throwWithMessage(
                "Could not write stream for log file [{$this->log_file}].",
                $message
            );
        }
        // @codeCoverageIgnoreStart
    }

    private function linePrefix(): string
    {
        $date = date('d-M-Y G:i:s') . ' UTC';

        return "[{$date}] {$this->channel}.";
    }

    /**
     * @return never
     */
    private function throwWithMessage(string $message, string $log_message)
    {
        throw new RuntimeException(
            "{$message}\nThe exception occurred while trying to log the message:\n{$log_message}"
        );
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function is_string;
use function sprintf;

final class ConditionalLogger extends AbstractLogger
{
    private const MAP = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private LoggerInterface $logger;

    private int $min_level_index;

    public function __construct(LoggerInterface $logger, string $min_level = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->ensureValidLogLevel($min_level);
        $this->min_level_index = self::MAP[$min_level];
    }

    public function log($level, $message, array $context = [])
    {
        $this->ensureValidLogLevel($level);

        if (self::MAP[$level] < $this->min_level_index) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

    /**
     * @param mixed $level
     *
     * @psalm-assert "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency" $level
     */
    private function ensureValidLogLevel($level): void
    {
        if (! is_string($level) || ! isset(self::MAP[$level])) {
            throw new InvalidArgumentException(sprintf('Invalid log level [%s] provided', (string) $level));
        }
    }
}

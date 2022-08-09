<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger\Formatter;

use Psr\Log\LoggerInterface;

/**
 * Formats a message that was passed to {@see LoggerInterface::log()}.
 */
interface Formatter
{
    public function format(string $level, string $message, array $context = [], string $line_prefix = ''): string;
}

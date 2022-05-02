<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

use InvalidArgumentException;
use Snicco\Component\BetterWPCLI\Verbosity;

use function sprintf;
use function substr;

use const PHP_EOL;

/**
 * The buffered output keeps only the N most recent characters.
 */
final class BufferedOutput extends OutputWithVerbosity
{
    private string $buffer = '';

    private int $max_length;

    public function __construct(int $max_length, int $verbosity = Verbosity::NORMAL, bool $decorated = false)
    {
        if ($max_length <= 0) {
            throw new InvalidArgumentException(
                sprintf('$max_length has to be a positive integer. Got %d.', $max_length)
            );
        }

        parent::__construct($verbosity, $decorated);
        $this->max_length = $max_length;
    }

    public function fetchAndEmpty(): string
    {
        $content = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= PHP_EOL;
        }

        $this->buffer = (string) substr($this->buffer, 0 - $this->max_length);
    }
}

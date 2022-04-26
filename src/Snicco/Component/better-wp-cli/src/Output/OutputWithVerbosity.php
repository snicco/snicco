<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

use Snicco\Component\BetterWPCLI\Verbosity;

use function str_repeat;

use const PHP_EOL;

abstract class OutputWithVerbosity implements Output
{
    /**
     * @psalm-readonly
     */
    private int $verbosity;

    private int $all_verbosities;

    private bool $decorated;

    public function __construct(int $verbosity = Verbosity::NORMAL, bool $decorated = false)
    {
        $this->verbosity = $verbosity;
        $this->all_verbosities =
            Verbosity::QUIET | Verbosity::NORMAL | Verbosity::VERBOSE | Verbosity::VERY_VERBOSE | Verbosity::DEBUG;
        $this->decorated = $decorated;
    }

    public function write($messages, bool $newline = false, int $options = 0): void
    {
        $message_verbosity = $this->all_verbosities & $options ?: Verbosity::NORMAL;

        if ($message_verbosity > $this->verbosity) {
            return;
        }

        if (! is_iterable($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            $this->doWrite($message, $newline);
        }
    }

    public function newLine(int $count = 1): void
    {
        if ($count < 1) {
            return;
        }

        $this->write(str_repeat(PHP_EOL, $count));
    }

    public function writeln($messages, int $options = 0): void
    {
        $this->write($messages, true, $options);
    }

    public function supportsDecoration(): bool
    {
        return $this->decorated;
    }

    public function verbosity(): int
    {
        return $this->verbosity;
    }

    public function isDebug(): bool
    {
        return Verbosity::DEBUG <= $this->verbosity;
    }

    public function isQuiet(): bool
    {
        return Verbosity::QUIET === $this->verbosity;
    }

    public function isVerbose(): bool
    {
        return Verbosity::VERBOSE <= $this->verbosity;
    }

    public function isVeryVerbose(): bool
    {
        return Verbosity::VERY_VERBOSE <= $this->verbosity;
    }

    abstract protected function doWrite(string $message, bool $newline): void;
}

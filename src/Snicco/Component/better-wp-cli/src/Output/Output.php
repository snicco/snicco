<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

interface Output
{
    /**
     * @param string|iterable<string> $messages
     */
    public function write($messages, bool $newline = false, int $options = 0): void;

    /**
     * @param string|iterable<string> $messages
     */
    public function writeln($messages, int $options = 0): void;

    public function newLine(int $count = 1): void;

    public function supportsDecoration(): bool;

    public function verbosity(): int;

    /**
     * Returns whether verbosity is quiet (-q).
     */
    public function isQuiet(): bool;

    /**
     * Returns whether verbosity is verbose (--v).
     */
    public function isVerbose(): bool;

    /**
     * Returns whether verbosity is very verbose (--vv).
     */
    public function isVeryVerbose(): bool;

    /**
     * Returns whether verbosity is debug (--vvv/--debug).
     */
    public function isDebug(): bool;
}

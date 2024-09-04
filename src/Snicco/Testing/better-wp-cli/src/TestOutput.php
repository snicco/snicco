<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing;

use Snicco\Component\BetterWPCLI\Output\ConsoleOutputInterface;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Output\OutputWithVerbosity;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI\Testing
 */
final class TestOutput extends OutputWithVerbosity implements ConsoleOutputInterface
{
    private Output $stdout;

    private Output $stderr;

    public function __construct(
        int $verbosity,
        bool $colors,
        OutputWithVerbosity $stdout,
        OutputWithVerbosity $stderr
    ) {
        parent::__construct($verbosity, $colors);
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function errorOutput(): Output
    {
        return $this->stderr;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->stdout->write($message, $newline);
    }
}

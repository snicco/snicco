<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

use RuntimeException;
use Snicco\Component\BetterWPCLI\Check;
use Snicco\Component\BetterWPCLI\Verbosity;

use function defined;
use function fopen;

use const STDERR;
use const STDOUT;

final class ConsoleOutput extends OutputWithVerbosity implements ConsoleOutputInterface
{
    private StreamOutput $stdout;

    private StreamOutput $stderr;

    public function __construct(
        int $verbosity = Verbosity::NORMAL,
        bool $colors_stdout = null,
        bool $colors_stderr = null
    ) {
        $this->stdout = new StreamOutput($this->openStdout(), $verbosity, $colors_stdout);
        $this->stderr = new StreamOutput($this->openStderr(), $verbosity, $colors_stderr);
        parent::__construct($verbosity, $this->stdout->supportsDecoration());
    }

    public function errorOutput(): StreamOutput
    {
        return $this->stderr;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        // @codeCoverageIgnoreStart
        $this->stdout->writeStream($message, $newline);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return resource
     */
    private function openStdout()
    {
        if (defined('STDOUT')) {
            $stdout = STDOUT;
        // @codeCoverageIgnoreStart
        } else {
            $stdout = @fopen('php://stdout', 'w');
            $stdout = $stdout ?: @fopen('php://output', 'w');
            // @codeCoverageIgnoreEnd
        }

        if (! Check::isStream($stdout)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not open stdout stream.');
            // @codeCoverageIgnoreEnd
        }

        return $stdout;
    }

    /**
     * @return resource
     */
    private function openStderr()
    {
        if (defined('STDERR')) {
            $stderr = STDERR;
        // @codeCoverageIgnoreStart
        } else {
            $stderr = @fopen('php://stderr', 'w');
            $stderr = $stderr ?: @fopen('php://output', 'w');
            // @codeCoverageIgnoreEnd
        }

        if (! Check::isStream($stderr)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not open stderr stream.');
            // @codeCoverageIgnoreEnd
        }

        return $stderr;
    }
}

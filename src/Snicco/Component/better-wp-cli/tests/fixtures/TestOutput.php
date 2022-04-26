<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures;

use Snicco\Component\BetterWPCLI\Output\OutputWithVerbosity;

final class TestOutput extends OutputWithVerbosity
{
    /**
     * @var list<string>
     *
     * @psalm-readonly-allow-private-mutation
     */
    public array $lines = [];

    protected function doWrite(string $message, bool $newline): void
    {
        if ($newline) {
            $message .= PHP_EOL;
        }

        $this->lines[] = $message;
    }
}

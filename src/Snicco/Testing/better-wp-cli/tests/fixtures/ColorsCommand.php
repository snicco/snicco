<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutputInterface;
use Snicco\Component\BetterWPCLI\Output\Output;

final class ColorsCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        /** @var ConsoleOutputInterface $output */
        if ($output->supportsDecoration()) {
            $output->writeln('colors');
        }

        if ($output->errorOutput()->supportsDecoration()) {
            $output->errorOutput()
                ->writeln('colors');
        }

        return 0;
    }
}

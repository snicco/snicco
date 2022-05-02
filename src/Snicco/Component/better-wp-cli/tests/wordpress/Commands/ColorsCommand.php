<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class ColorsCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        if ($output->supportsDecoration()) {
            $output->writeln('DECORATED');
        } else {
            $output->writeln('NOT DECORATED');
        }

        return 0;
    }
}

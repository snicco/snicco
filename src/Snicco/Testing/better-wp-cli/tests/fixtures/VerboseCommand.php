<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Verbosity;

final class VerboseCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $output->writeln('verbose-only', Verbosity::VERBOSE);

        return 0;
    }
}

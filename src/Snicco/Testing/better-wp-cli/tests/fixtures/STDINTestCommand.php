<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class STDINTestCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $read = [$input->getStream()];
        $write = [];
        $except = [];
        $timeout = 0;

        $has_data = @stream_select($read, $write, $except, $timeout);

        $output->writeln($has_data ? 'PIPE' : 'NOT_A_PIPE');
        $output->writeln((string) stream_get_contents($input->getStream()));

        return 0;
    }
}

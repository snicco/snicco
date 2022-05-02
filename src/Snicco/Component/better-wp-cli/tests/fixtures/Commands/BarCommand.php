<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class BarCommand extends Command
{
    protected static string $short_description = 'This is the bar command';

    public function execute(Input $input, Output $output): int
    {
        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputOption;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

final class OptionCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $option = $input->getOption('option');

        $output->writeln((string) $option);

        return 0;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with(new InputOption('option', '', InputOption::REQUIRED));
    }
}

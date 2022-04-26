<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

final class ArgumentCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $arg = $input->getArgument('name');

        $output->writeln('Hello ' . (string) $arg);

        return Command::SUCCESS;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with(new InputArgument('name'));
    }
}

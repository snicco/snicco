<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

use function implode;

final class RepeatingArgumentCommand extends Command
{
    protected static string $name = 'repeating';

    public function execute(Input $input, Output $output): int
    {
        $arg = (array) $input->getRepeatingArgument('names');

        $output->writeln('Hello ' . implode(',', $arg));

        return Command::SUCCESS;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with(
            new InputArgument('names', '', InputArgument::REQUIRED | InputArgument::REPEATING)
        );
    }
}

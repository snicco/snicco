<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;

final class PromptCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $style = new SniccoStyle($input, $output);

        if ($style->confirm('Proceed?')) {
            $output->writeLn('You answered yes once.');

            if ($style->confirm('Are you really sure?')) {
                $output->writeLn('You answered yes twice.');
            }
        } else {
            $output->writeLn('You answered no.');
        }

        return 0;
    }
}

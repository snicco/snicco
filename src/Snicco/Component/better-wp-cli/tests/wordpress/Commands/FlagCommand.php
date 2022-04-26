<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

final class FlagCommand extends Command
{
    public function execute(Input $input, Output $output): int
    {
        $flag = $input->getFlag('flag');

        if (true === $flag) {
            $write = 'TRUE';
        } elseif (false === $flag) {
            $write = 'FALSE';
        } else {
            $write = 'NULL';
        }

        $output->writeln($write);

        return 0;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with(new InputFlag('flag'));
    }
}

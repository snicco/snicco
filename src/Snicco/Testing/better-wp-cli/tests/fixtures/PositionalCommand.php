<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;
use Snicco\Component\BetterWPCLI\Synopsis\InputOption;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

use function implode;

final class PositionalCommand extends Command
{
    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with([
            new InputArgument('foo'),
            new InputArgument('bar', 'asd', InputArgument::REPEATING | InputArgument::REQUIRED),
            new InputOption('baz'),
            new InputFlag('biz'),
        ]);
    }

    public function execute(Input $input, Output $output): int
    {
        $foo = (string) $input->getArgument('foo');
        $bar = implode('', (array) $input->getRepeatingArgument('bar'));
        $baz = (string) $input->getOption('baz');
        $biz = (string) $input->getFlag('biz');

        $output->write($foo . $bar . $baz . $biz);

        return 0;
    }
}

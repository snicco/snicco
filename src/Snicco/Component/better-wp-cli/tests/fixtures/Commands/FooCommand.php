<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

final class FooCommand extends Command
{
    protected static string $name = 'foo_command_custom';

    protected static string $long_description = 'long';

    public static function synopsis(): Synopsis
    {
        return new Synopsis(
            new InputArgument('foo', 'foo description', InputArgument::REQUIRED),
            new InputArgument('bar', 'bar description', InputArgument::REQUIRED)
        );
    }

    public function execute(Input $input, Output $output): int
    {
        $output->writeln('FOO');

        return 0;
    }
}

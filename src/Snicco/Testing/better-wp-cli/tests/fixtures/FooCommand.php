<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\fixtures;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

final class FooCommand extends Command
{
    private int $forced_status_code;

    public function __construct(int $forced_status_code = 0)
    {
        $this->forced_status_code = $forced_status_code;
    }

    public function execute(Input $input, Output $output): int
    {
        $output->writeln((string) $input->getArgument('stdin_value'));

        $style = new SniccoStyle($input, $output);

        $style->success((string) $input->getArgument('stdout_value'));

        return $this->forced_status_code;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with([
            new InputArgument('stdin_value'),
            new InputArgument('stdout_value'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class ExitCodeCommand extends Command
{
    private int $exit_code;

    public function __construct(int $exit_code)
    {
        $this->exit_code = $exit_code;
    }

    public function execute(Input $input, Output $output): int
    {
        return $this->exit_code;
    }
}

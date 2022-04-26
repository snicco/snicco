<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class TriggerErrorCommand extends Command
{
    /**
     * @var callable():void
     */
    private $trigger_error;

    /**
     * @param callable():void $trigger_error
     */
    public function __construct(callable $trigger_error)
    {
        $this->trigger_error = $trigger_error;
    }

    public function execute(Input $input, Output $output): int
    {
        ($this->trigger_error)();

        return 0;
    }
}

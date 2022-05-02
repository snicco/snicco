<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class ExceptionCommand extends Command
{
    /**
     * @var callable():never
     */
    private $throw_exception;

    /**
     * @param callable():never $throw_exception
     */
    public function __construct(callable $throw_exception)
    {
        $this->throw_exception = $throw_exception;
    }

    public function execute(Input $input, Output $output): int
    {
        ($this->throw_exception)();
    }
}

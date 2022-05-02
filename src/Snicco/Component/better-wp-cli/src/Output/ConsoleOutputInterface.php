<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

interface ConsoleOutputInterface extends Output
{
    public function errorOutput(): Output;
}

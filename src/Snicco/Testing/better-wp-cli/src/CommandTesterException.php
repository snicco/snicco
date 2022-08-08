<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing;

use Symfony\Component\Console\Exception\LogicException;

final class CommandTesterException extends LogicException
{
    public static function becauseNoCommandWasRun(): self
    {
        return new self('Did you forget to call CommandTester::run() ?');
    }
}

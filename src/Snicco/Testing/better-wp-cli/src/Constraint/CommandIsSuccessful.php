<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Snicco\Component\BetterWPCLI\Command;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI\Testing
 */
final class CommandIsSuccessful extends Constraint
{
    public function toString(): string
    {
        return 'is successful';
    }

    protected function matches($other): bool
    {
        return Command::SUCCESS === $other;
    }

    protected function failureDescription($other): string
    {
        return 'the command ' . $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        return sprintf('Command returned exit code %d.', (int) $other);
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

use function sprintf;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI\Testing
 */
final class StatusCode extends Constraint
{
    private int $expected;

    public function __construct(int $expected)
    {
        $this->expected = $expected;
    }

    public function toString(): string
    {
        return 'the command returned the status code ' . (string) $this->expected;
    }

    protected function matches($other): bool
    {
        return $this->expected === $other;
    }

    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        return sprintf('Command returned exit code %d.', (int) $other);
    }
}

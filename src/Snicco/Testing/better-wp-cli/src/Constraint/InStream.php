<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

use function strpos;

/**
 * @interal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI\Testing
 */
final class InStream extends Constraint
{
    private string $expected;

    private string $type;

    public function __construct(string $expected, string $type)
    {
        $this->expected = $expected;
        $this->type = $type;
    }

    public function toString(): string
    {
        return "{$this->type} contains [{$this->expected}]";
    }

    protected function matches($other): bool
    {
        return false !== strpos((string) $other, $this->expected);
    }

    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        /** @var string $other */
        return "The command output was:\n{$other}";
    }
}

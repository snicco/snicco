<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Synopsis;

/**
 * @interal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI
 */
interface InputDefinition
{
    public function name(): string;

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array;
}

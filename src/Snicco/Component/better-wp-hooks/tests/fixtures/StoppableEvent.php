<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\fixtures;

use Psr\EventDispatcher\StoppableEventInterface;
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

final class StoppableEvent implements StoppableEventInterface, ExposeToWP
{
    public bool $stopped = false;

    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}

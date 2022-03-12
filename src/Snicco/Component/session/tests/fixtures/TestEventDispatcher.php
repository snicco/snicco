<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\fixtures;

use Closure;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;

use function get_class;

final class TestEventDispatcher implements SessionEventDispatcher
{
    /**
     * @var array<string,Closure>
     */
    private array $listeners = [];

    /**
     * @param array<string, Closure> $listeners
     */
    public function __construct(array $listeners)
    {
        $this->listeners = $listeners;
    }

    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $listener = $this->listeners[get_class($event)];

            $listener($event);
        }
    }
}

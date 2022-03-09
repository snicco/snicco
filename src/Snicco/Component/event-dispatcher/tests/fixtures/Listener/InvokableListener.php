<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Listener;

use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;

class InvokableListener
{
    use AssertListenerResponse;

    public function __invoke($foo, $bar)
    {
        $this->respondedToEvent('foo_event', static::class, $foo . $bar);
    }
}

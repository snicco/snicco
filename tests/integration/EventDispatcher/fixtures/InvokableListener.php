<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher\fixtures;

use Tests\concerns\AssertListenerResponse;

class InvokableListener
{
    
    use AssertListenerResponse;
    
    public function __invoke($foo, $bar)
    {
        $this->respondedToEvent('foo_event', static::class, $foo.$bar);
    }
    
}
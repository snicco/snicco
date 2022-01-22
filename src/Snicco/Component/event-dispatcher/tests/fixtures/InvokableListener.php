<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

class InvokableListener
{
    
    use AssertListenerResponse;
    
    public function __invoke($foo, $bar)
    {
        $this->respondedToEvent('foo_event', static::class, $foo.$bar);
    }
    
}
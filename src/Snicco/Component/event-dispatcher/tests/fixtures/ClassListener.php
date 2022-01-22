<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

class ClassListener
{
    
    use AssertListenerResponse;
    
    public function handle(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener::class, $event->val);
    }
    
    public function customHandleMethod($event)
    {
        $this->respondedToEvent(get_class($event), ClassListener::class, $event->val);
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Tests\BetterWPHooks\helpers\AssertListenerResponse;

class ClassListener2
{
    
    use AssertListenerResponse;
    
    public function handle(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener2::class, $event->val);
    }
    
}
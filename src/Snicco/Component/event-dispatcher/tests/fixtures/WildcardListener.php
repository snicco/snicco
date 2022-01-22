<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

final class WildcardListener
{
    
    use AssertListenerResponse;
    
    public function customMethod1($event_name, $user_name)
    {
        $this->respondedToEvent($event_name, WildcardListener::class, $user_name);
    }
    
}
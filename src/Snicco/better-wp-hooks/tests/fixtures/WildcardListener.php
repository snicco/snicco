<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Tests\BetterWPHooks\helpers\AssertListenerResponse;

final class WildcardListener
{
    
    use AssertListenerResponse;
    
    public function customMethod1($event_name, $user_name)
    {
        $this->respondedToEvent($event_name, WildcardListener::class, $user_name);
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\GenericEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\EventParser;

final class GenericEventParser implements EventParser
{
    
    public function transformEventNameAndPayload($event, array $payload) :array
    {
        if ($event instanceof Event) {
            return [get_class($event), $event];
        }
        
        return [$event, new GenericEvent($payload)];
    }
    
}